<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * **S79** — une dépendance de développement ne se charge pas en production.
 *
 * Constat du 2026-10-05 sur le serveur : `composer install --no-dev` — ce que fait
 * `deploy.sh` — puis `php artisan` échouaient. Deux fuites :
 *
 *  1. `config/app.php` enregistrait `Barryvdh\Debugbar\ServiceProvider` et sa façade
 *     sans condition, alors que `barryvdh/laravel-debugbar` est en `require-dev` :
 *     `Class "Barryvdh\Debugbar\ServiceProvider" not found` dès `package:discover`,
 *     c'est-à-dire pendant `composer install` lui-même ;
 *  2. sept semences lisaient `Faker\Factory`, et `fakerphp/faker` était en
 *     `require-dev` : `db:seed` tombait sur une installation `--no-dev`.
 *
 * Douzième filet : il lit `composer.lock`, reconstruit les espaces de noms des
 * paquets de **dev**, et refuse toute référence à l'un d'eux hors de `tests/` —
 * sauf la seule tolérée, la barre de débogage, qui doit vivre derrière
 * `class_exists` et `app.debug`, et rester hors de la découverte automatique.
 */
class DevDependencyLeakTest extends TestCase
{
    /** Dossiers qui s'exécutent en production (ou au déploiement : les semences). */
    private const PRODUCTION = ['app', 'bootstrap/app.php', 'config', 'database', 'routes', 'resources/views'];

    /**
     * Les seules références tolérées à un paquet de dev, fichier par fichier : la barre de
     * débogage, enregistrée par AppServiceProvider sous garde (vérifiée plus bas).
     */
    private const TOLEREES = [
        'app/Providers/AppServiceProvider.php' => ['Barryvdh\Debugbar\\'],
        'config/app.php' => [], // seulement des commentaires, vérifié par le test dédié
    ];

    private function lock(): array
    {
        return json_decode(file_get_contents(base_path('composer.lock')), true);
    }

    /**
     * @return array<string, string> préfixe d'espace de noms => paquet de dev
     *
     * ⚠️ `laravel/pint` embarque sa propre application sous `App\` : un préfixe qui
     * est aussi l'un des nôtres (`composer.json` → `autoload.psr-4`) désigne notre
     * code, pas le paquet. Il est écarté.
     */
    private function espacesDeDev(): array
    {
        $json = json_decode(file_get_contents(base_path('composer.json')), true);
        $notres = array_map(fn ($p) => rtrim($p, '\\') . '\\', array_keys($json['autoload']['psr-4'] ?? []));

        $prefixes = [];
        foreach ($this->lock()['packages-dev'] as $paquet) {
            foreach (['psr-4', 'psr-0'] as $type) {
                foreach ($paquet['autoload'][$type] ?? [] as $prefixe => $chemins) {
                    $prefixe = rtrim($prefixe, '\\') . '\\';
                    if ($prefixe !== '\\' && !in_array($prefixe, $notres, true)) {
                        $prefixes[$prefixe] = $paquet['name'];
                    }
                }
            }
        }

        return $prefixes;
    }

    /** @return string[] chemins relatifs des fichiers PHP de production */
    private function fichiersDeProduction(): array
    {
        $fichiers = [];
        foreach (self::PRODUCTION as $racine) {
            $chemin = base_path($racine);
            if (is_file($chemin)) {
                $fichiers[] = $racine;
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chemin, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->getExtension() === 'php') {
                    $fichiers[] = $racine . '/' . ltrim(str_replace($chemin, '', $f->getPathname()), '/');
                }
            }
        }
        sort($fichiers);

        return $fichiers;
    }

    /** Les noms de classes pleinement qualifiés d'un source, commentaires retirés. */
    private function referencesDe(string $source): array
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }
        preg_match_all('/\\\\?([A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)+)/', $code, $m);

        return array_unique($m[1]);
    }

    // ---- le filet -------------------------------------------------------------------------

    public function test_le_code_de_production_ne_reference_aucun_paquet_de_dev(): void
    {
        $espaces = $this->espacesDeDev();
        $this->assertArrayHasKey('Barryvdh\Debugbar\\', $espaces, 'la barre de débogage est bien un paquet de dev');
        $this->assertArrayNotHasKey('Faker\\', $espaces, 'fakerphp/faker est en require depuis S79 : les semences en dépendent');

        $fuites = [];
        foreach ($this->fichiersDeProduction() as $fichier) {
            $tolerees = self::TOLEREES[$fichier] ?? [];
            foreach ($this->referencesDe(file_get_contents(base_path($fichier))) as $classe) {
                foreach ($espaces as $prefixe => $paquet) {
                    if (!str_starts_with($classe, $prefixe)) {
                        continue;
                    }
                    $toleree = false;
                    foreach ($tolerees as $t) {
                        $toleree = $toleree || str_starts_with($classe, $t);
                    }
                    if (!$toleree) {
                        $fuites[] = "{$fichier} → {$classe} ({$paquet}, require-dev)";
                    }
                }
            }
        }

        $this->assertSame([], $fuites,
            "Une dépendance de dev est référencée par du code qui tourne en production : sur le serveur, `composer install --no-dev` ne l'installe pas.\n" . implode("\n", $fuites));
    }

    /** Faker : les semences en dépendent, donc il est en `require`, pas en `require-dev`. */
    public function test_faker_est_une_dependance_de_production_parce_que_les_semences_l_utilisent(): void
    {
        $json = json_decode(file_get_contents(base_path('composer.json')), true);
        $lock = $this->lock();

        $this->assertArrayHasKey('fakerphp/faker', $json['require']);
        $this->assertArrayNotHasKey('fakerphp/faker', $json['require-dev']);
        $this->assertContains('fakerphp/faker', array_column($lock['packages'], 'name'), 'le verrou suit composer.json');
        $this->assertNotContains('fakerphp/faker', array_column($lock['packages-dev'], 'name'));

        // Et la raison existe toujours : des semences lisent Faker.
        $semences = array_filter($this->fichiersDeProduction(), fn ($f) => str_starts_with($f, 'database/seeders/')
            && str_contains(file_get_contents(base_path($f)), 'Faker\\Factory'));
        $this->assertNotEmpty($semences, 'plus aucune semence ne lit Faker : il peut redevenir une dépendance de dev');
    }

    // ---- la barre de débogage, derrière sa garde --------------------------------------------

    public function test_la_barre_de_debogage_n_est_plus_enregistree_sans_condition(): void
    {
        $config = file_get_contents(base_path('config/app.php'));
        $this->assertSame([], array_values(array_filter($this->referencesDe($config), fn ($c) => str_starts_with($c, 'Barryvdh\Debugbar'))),
            'config/app.php ne nomme plus Barryvdh\Debugbar hors commentaire : une classe absente dans `providers` casse package:discover');

        $json = json_decode(file_get_contents(base_path('composer.json')), true);
        $this->assertContains('barryvdh/laravel-debugbar', $json['extra']['laravel']['dont-discover'] ?? [],
            'la découverte automatique rebrancherait la barre dans le dos de la garde');

        $provider = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));
        $this->assertSame(1, preg_match('/function registerDebugbar\(\): void\s*\{(.*?)\n    \}/s', $provider, $m));
        $garde = $m[1];
        $enregistrement = strpos($garde, 'register(\Barryvdh\Debugbar\ServiceProvider::class)');
        $this->assertNotFalse($enregistrement);
        $condition = strpos($garde, "!config('app.debug') || !class_exists(\\Barryvdh\\Debugbar\\ServiceProvider::class)");
        $this->assertNotFalse($condition, 'la garde lit app.debug ET class_exists');
        $this->assertLessThan($enregistrement, $condition, 'la garde précède l\'enregistrement');
        $this->assertStringContainsString('return;', substr($garde, $condition, $enregistrement - $condition));
    }

    /** Sous PHPUnit la classe existe ; c'est donc `app.debug` qui décide. Le socle la chargeait toujours. */
    public function test_sans_debogage_la_barre_ne_se_charge_pas_meme_installee(): void
    {
        $this->assertTrue(class_exists(\Barryvdh\Debugbar\ServiceProvider::class), 'les dépendances de dev sont là sous PHPUnit');

        $app = $this->createApplication();
        $this->assertSame(config('app.debug'), $app->make('config')->get('app.debug'));

        $eteinte = $this->applicationAvecDebug(false);
        $this->assertFalse($eteinte->bound('debugbar'), 'APP_DEBUG=false : la barre n\'est pas enregistrée');

        $allumee = $this->applicationAvecDebug(true);
        $this->assertTrue($allumee->bound('debugbar'), 'APP_DEBUG=true : la barre est enregistrée, comme avant');
    }

    /** Une application démarrée avec `app.debug` forcé AVANT l'enregistrement des fournisseurs. */
    private function applicationAvecDebug(bool $debug): \Illuminate\Foundation\Application
    {
        $app = require base_path('bootstrap/app.php');
        $app->afterLoadingEnvironment(fn () => null);
        $app->booting(fn () => null);
        $app->beforeBootstrapping(\Illuminate\Foundation\Bootstrap\RegisterProviders::class, function ($app) use ($debug) {
            $app->make('config')->set('app.debug', $debug);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}
