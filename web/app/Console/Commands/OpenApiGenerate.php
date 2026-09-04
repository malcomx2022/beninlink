<?php

namespace App\Console\Commands;

use App\Services\OpenApi\SpecGenerator;
use Illuminate\Console\Command;

/**
 * Écrit la spécification OpenAPI de /api/v10 dans public/openapi/v10.json.
 *
 * À relancer après tout changement de routes ou d'overlay ; le fichier est
 * versionné pour que les apps puissent le lire sans serveur. Le test
 * OpenApiSpecTest signale les routes non documentées et les orphelins.
 */
class OpenApiGenerate extends Command
{
    protected $signature = 'openapi:generate {--check : Ne rien écrire, seulement signaler les écarts}';

    protected $description = 'Génère la spécification OpenAPI de l\'API /api/v10 (chantier 7)';

    public function handle(SpecGenerator $generator): int
    {
        $orphans = $generator->orphans();
        $undocumented = $generator->undocumented();

        foreach ($orphans as $key) {
            $this->error("Overlay orphelin (aucune route) : {$key}");
        }
        foreach ($undocumented as $key) {
            $this->warn("Route sans overlay (résumé par défaut) : {$key}");
        }

        if ($this->option('check')) {
            return $orphans === [] ? self::SUCCESS : self::FAILURE;
        }

        $spec = $generator->generate();
        $path = config('openapi.output');
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

        $this->info(sprintf('%d chemins écrits dans %s', count($spec['paths']), $path));

        return $orphans === [] ? self::SUCCESS : self::FAILURE;
    }
}
