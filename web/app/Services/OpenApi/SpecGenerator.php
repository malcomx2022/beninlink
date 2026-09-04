<?php

namespace App\Services\OpenApi;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;

/**
 * Génère la spécification OpenAPI 3.0 de `/api/v10` depuis le routeur.
 *
 * Le routeur est la vérité : chaque route enregistrée sous le préfixe devient
 * une opération, avec sa sécurité déduite des middlewares réels (`CheckApiKey`,
 * `auth:sanctum`, `throttle`). L'overlay (`resources/openapi/overlay.php`)
 * apporte ce que le code ne dit pas — résumés, corps de requête, schémas de
 * réponse — et ne peut décrire qu'une route existante (voir `orphans()`).
 */
class SpecGenerator
{
    public function __construct(private ?array $overlay = null)
    {
        $this->overlay ??= require resource_path('openapi/overlay.php');
    }

    public function generate(): array
    {
        $paths = [];
        $tags = [];

        foreach ($this->apiRoutes() as $route) {
            $path = $this->openApiPath($route);
            foreach ($this->methodsOf($route) as $method) {
                $operation = $this->operation($route, $method, $path);
                $paths[$path][strtolower($method)] = $operation;
                foreach ($operation['tags'] as $tag) {
                    $tags[$tag] = true;
                }
            }
        }

        ksort($paths);
        $tagList = array_map(fn ($t) => ['name' => $t], array_keys($tags));
        usort($tagList, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('openapi.title'),
                'version' => config('openapi.version'),
                'description' => config('openapi.description'),
            ],
            'servers' => config('openapi.servers'),
            'tags' => $tagList,
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'apiKey' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'apiKey',
                        'description' => 'Clé d\'installation (`API_KEY` du .env). Un filtre, pas une authentification.',
                    ],
                    'bearer' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Jeton personnel Sanctum obtenu par `POST /signin`, `POST /otp-verification` ou `POST /deliveryman/login`.',
                    ],
                ],
                'schemas' => $this->overlay['schemas'] ?? [],
            ],
        ];
    }

    /** Clés « METHOD chemin » de l'overlay qui ne correspondent à aucune route. */
    public function orphans(): array
    {
        $known = [];
        foreach ($this->apiRoutes() as $route) {
            foreach ($this->methodsOf($route) as $method) {
                $known[$method . ' ' . $this->relativePath($route)] = true;
            }
        }

        return array_values(array_filter(
            array_keys($this->overlay['operations'] ?? []),
            fn ($key) => !isset($known[$key])
        ));
    }

    /** Routes documentées sans entrée d'overlay (résumé déduit du nom). */
    public function undocumented(): array
    {
        $missing = [];
        foreach ($this->apiRoutes() as $route) {
            foreach ($this->methodsOf($route) as $method) {
                $key = $method . ' ' . $this->relativePath($route);
                if (!isset($this->overlay['operations'][$key])) {
                    $missing[] = $key;
                }
            }
        }

        return $missing;
    }

    /** @return Route[] */
    public function apiRoutes(): array
    {
        $prefix = trim((string) config('openapi.route_prefix'), '/');

        return array_values(array_filter(
            Router::getRoutes()->getRoutes(),
            fn (Route $route) => Str::startsWith($route->uri(), $prefix . '/')
        ));
    }

    private function methodsOf(Route $route): array
    {
        return array_values(array_filter($route->methods(), fn ($m) => !in_array($m, ['HEAD', 'OPTIONS'], true)));
    }

    /** Chemin sans le préfixe, ex. `parcel/details/{id}`. */
    public function relativePath(Route $route): string
    {
        $prefix = trim((string) config('openapi.route_prefix'), '/');

        return Str::after($route->uri(), $prefix . '/');
    }

    private function openApiPath(Route $route): string
    {
        return '/' . $this->relativePath($route);
    }

    private function operation(Route $route, string $method, string $path): array
    {
        $key = $method . ' ' . $this->relativePath($route);
        $doc = $this->overlay['operations'][$key] ?? [];
        $middleware = $route->gatherMiddleware();

        $bearer = in_array('auth:sanctum', $middleware, true);
        $apiKey = in_array('CheckApiKey', $middleware, true);
        $throttle = collect($middleware)->first(fn ($m) => Str::startsWith($m, 'throttle:'));

        $security = [];
        if ($bearer && $apiKey) {
            $security[] = ['apiKey' => [], 'bearer' => []];
        } elseif ($bearer) {
            $security[] = ['bearer' => []];
        } elseif ($apiKey) {
            $security[] = ['apiKey' => []];
        }

        $parameters = $doc['parameters'] ?? [];
        $declared = array_column($parameters, 'name');
        foreach ($route->parameterNames() as $name) {
            if (!in_array($name, $declared, true)) {
                $parameters[] = [
                    'name' => $name,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string'],
                ];
            }
        }

        $responses = $doc['responses'] ?? [];
        $responses += ['200' => ['description' => 'Succès', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Envelope']]]]];
        if ($apiKey) {
            $responses += ['400' => ['description' => 'En-tête `apiKey` absent ou invalide']];
        }
        if ($bearer) {
            $responses += ['401' => ['description' => 'Jeton absent, expiré ou révoqué']];
        }
        if ($route->parameterNames() !== []) {
            $responses += ['404' => ['description' => 'Ressource introuvable ou hors du périmètre du compte connecté']];
        }
        if (in_array($method, ['POST', 'PUT'], true)) {
            $responses += ['422' => ['description' => 'Erreurs de validation', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ValidationError']]]]];
        }
        if ($throttle) {
            $responses += ['429' => ['description' => 'Trop de requêtes (' . $throttle . ')']];
        }
        ksort($responses);

        $operation = [
            'operationId' => $this->operationId($route, $method),
            'tags' => [$doc['tag'] ?? $this->defaultTag($route)],
            'summary' => $doc['summary'] ?? $this->defaultSummary($route, $method),
            'description' => $doc['description'] ?? null,
            'parameters' => $parameters,
            'requestBody' => $doc['requestBody'] ?? null,
            'responses' => $responses,
            'security' => $security,
            'x-middleware' => array_values($middleware),
            'x-documented' => isset($this->overlay['operations'][$key]),
        ];

        return array_filter($operation, fn ($v) => $v !== null && $v !== []) + ['security' => $security];
    }

    private function operationId(Route $route, string $method): string
    {
        $action = $route->getActionName();
        if (Str::contains($action, '@')) {
            [$class, $fn] = explode('@', $action);
            return Str::camel(class_basename($class) . '_' . $fn);
        }

        return Str::camel(strtolower($method) . '_' . Str::slug($this->relativePath($route), '_'));
    }

    private function defaultTag(Route $route): string
    {
        $path = $this->relativePath($route);
        if (Str::startsWith($path, 'deliveryman/')) {
            return 'Livreur';
        }

        return 'Divers';
    }

    private function defaultSummary(Route $route, string $method): string
    {
        return $method . ' ' . $this->relativePath($route);
    }
}
