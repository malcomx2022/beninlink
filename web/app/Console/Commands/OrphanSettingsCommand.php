<?php

namespace App\Console\Commands;

use App\Models\Backend\Setting;
use Illuminate\Console\Command;

/**
 * `php artisan beninlink:reglages-orphelins` — les lignes de `settings` écrites
 * sans société, avant le correctif du `fillable`.
 *
 * ## Ce qui s'est passé
 *
 * `Setting` ne déclarait pas `company_id` assignable en masse, alors que tout le
 * socle écrit ces lignes ainsi. Eloquent le laissait tomber en silence : la ligne
 * partait avec un locataire nul et devenait invisible à `scopeCompanywise()` et
 * à `globalSettings()`. Chaque enregistrement suivant en créait une de plus.
 *
 * ## Pourquoi cette commande ne rattache rien
 *
 * Deux écritures perdaient `company_id`, et elles sont désormais
 * **indiscernables** :
 *   - `SettingSeeder`, qui visait la société 1 (réglages de la plateforme) ;
 *   - `PayoutSetupRepository::update()`, qui visait la société **connectée** —
 *     donc n'importe quel locataire, et plusieurs d'entre eux.
 *
 * Rattacher ces lignes à la société 1 remettrait donc la clé secrète Stripe ou
 * PayPal d'un locataire entre les mains d'un autre : exactement la fuite
 * inter-locataires que le constat S6 avait fermée. Et cela rallumerait des
 * `*_status` sans qu'on l'ait demandé.
 *
 * La commande **constate**, sans jamais deviner. La remise en état se fait par
 * l'écran de réglages, société par société : c'est la seule voie qui attribue
 * chaque clé à son propriétaire réel.
 */
class OrphanSettingsCommand extends Command
{
    protected $signature = 'beninlink:reglages-orphelins
        {--purge : Supprimer ces lignes après les avoir listées}
        {--force : Autoriser --purge en production}';

    protected $description = 'Liste les lignes de settings écrites sans société (avant le correctif du fillable)';

    /** Clés dont la valeur est un secret : on ne montre jamais qu'elles existent. */
    private const SENSIBLE = ['secret', 'key', 'token', 'password', 'sid'];

    public function handle(): int
    {
        $orphelines = Setting::whereNull('company_id')->get(['id', 'key', 'created_at']);

        if ($orphelines->isEmpty()) {
            $this->info('Aucune ligne orpheline : cette installation est saine.');

            return self::SUCCESS;
        }

        $this->warn(sprintf(
            '%d ligne(s) de `settings` sans société, écrites avant le correctif du `fillable`.',
            $orphelines->count(),
        ));
        $this->line('Elles ne sont lues par personne : `globalSettings()` et `scopeCompanywise()` filtrent par société.');
        $this->newLine();

        $this->table(
            ['Clé', 'Lignes', 'Contenu'],
            $orphelines->groupBy('key')->map(fn ($lignes, $cle) => [
                $cle,
                $lignes->count(),
                $this->estSensible($cle) ? 'secret — non affiché' : 'réglage',
            ])->sortKeys()->values()->all(),
        );

        $this->newLine();
        $this->line('⚠️ Ces lignes ne sont PAS rattachables : le seeder visait la société 1 et');
        $this->line('   l\'écran de réglages visait la société connectée, sans qu\'on puisse les');
        $this->line('   distinguer aujourd\'hui. Les attribuer à la société 1 donnerait la clé');
        $this->line('   d\'un locataire à un autre.');
        $this->newLine();
        $this->line('👉 Remise en état : chaque société ressaisit ses clés depuis');
        $this->line('   Réglages → Pay-out. Les valeurs enregistrées depuis le correctif');
        $this->line('   portent bien leur société.');

        if (!$this->option('purge')) {
            $this->newLine();
            $this->comment('Ajouter --purge pour supprimer ces lignes une fois la ressaisie faite.');

            return self::SUCCESS;
        }

        if (app()->environment('production') && !$this->option('force')) {
            $this->newLine();
            $this->error('Refusé en production sans --force : la suppression est définitive.');

            return self::FAILURE;
        }

        $supprimees = Setting::whereNull('company_id')->delete();
        $this->newLine();
        $this->info(sprintf('%d ligne(s) supprimée(s).', $supprimees));

        return self::SUCCESS;
    }

    private function estSensible(string $cle): bool
    {
        foreach (self::SENSIBLE as $motif) {
            if (str_contains(strtolower($cle), $motif)) {
                return true;
            }
        }

        return false;
    }
}
