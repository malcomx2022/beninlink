<?php

namespace App\Console\Commands;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan beninlink:zones-tarifaires` — passer le barème aux zones (**D4**).
 *
 * ## Ce que le métier a tranché (2026-09-06)
 *
 *   - zones : **Cotonou, Périphérie, Intérieur, CEDEAO** ;
 *   - **délai global** : un supplément par délai, indépendant de la zone ;
 *   - **CEDEAO au forfait par pays**, pas au poids.
 *
 * ## Ce que la commande fait
 *
 * Elle crée les zones et les délais d'une société, puis convertit son barème
 * hérité : chaque ligne « catégorie × tranche » donne une ligne par zone,
 * **aux montants d'aujourd'hui** :
 *
 *     Cotonou    ← next_day      (le tarif intra-ville, délai standard)
 *     Périphérie ← sub_city
 *     Intérieur  ← outside_city
 *     CEDEAO     ← 0             (forfaits pays à saisir)
 *
 * ## Ce qu'elle ne cache pas
 *
 * Le supplément « jour même » est aujourd'hui **différent selon la tranche**
 * (`same_day − next_day`). Le modèle choisi n'en admet qu'un seul, global. La
 * commande affiche donc l'écart tranche par tranche et propose une valeur,
 * mais **ne la pose pas d'autorité** : c'est un prix, il appartient au métier.
 * Sans `--supplement=`, les délais sont créés à 0.
 */
class DeliveryZonesCommand extends Command
{
    protected $signature = 'beninlink:zones-tarifaires
        {--societe= : société à traiter ; par défaut toutes}
        {--supplement= : supplément « jour même », en FCFA entiers}
        {--appliquer : écrire les zones, les délais et le barème converti}';

    protected $description = 'Convertit le barème hérité en zones (D4) : constate, puis écrit sur demande';

    private const ZONES = [
        [DeliveryZone::COTONOU, 'Cotonou', 'next_day'],
        [DeliveryZone::PERIPHERIE, 'Périphérie', 'sub_city'],
        [DeliveryZone::INTERIEUR, 'Intérieur', 'outside_city'],
        [DeliveryZone::CEDEAO, 'CEDEAO', null],
    ];

    private const DELAIS = [
        [DeliveryDelay::SAME_DAY, 'Jour même'],
        [DeliveryDelay::NEXT_DAY, 'Lendemain'],
        [DeliveryDelay::STANDARD, 'Standard'],
    ];

    public function handle(): int
    {
        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::all();

        if ($societes->isEmpty()) {
            $this->error('Aucune société.');

            return self::FAILURE;
        }

        foreach ($societes as $societe) {
            $this->traiter($societe);
        }

        if (!$this->option('appliquer')) {
            $this->newLine();
            $this->comment('Constat seul. Ajouter --appliquer pour écrire (et --supplement= pour le délai « jour même »).');
        }

        return self::SUCCESS;
    }

    private function traiter(GeneralSettings $societe): void
    {
        $lignes = DeliveryCharge::where('company_id', $societe->id)->whereNull('zone_id')
            ->orderBy('category_id')->orderBy('weight')->get();

        $this->newLine();
        $this->line("<options=bold>{$societe->name}</> — {$lignes->count()} ligne(s) héritée(s)");

        if ($lignes->isEmpty()) {
            return;
        }

        // L'écart « jour même », tranche par tranche : c'est lui que le modèle
        // à supplément unique ne peut pas reproduire tel quel.
        $ecarts = $lignes->map(fn (DeliveryCharge $l) => (float) $l->same_day - (float) $l->next_day);
        $table = $lignes->map(fn (DeliveryCharge $l) => [
            $l->weight . ' kg',
            formatAmount((float) $l->next_day),
            formatAmount((float) $l->sub_city),
            formatAmount((float) $l->outside_city),
            formatAmount((float) $l->same_day - (float) $l->next_day),
        ])->all();

        $this->table(['Tranche', 'Cotonou', 'Périphérie', 'Intérieur', 'Écart « jour même »'], $table);

        $distincts = $ecarts->unique()->values();
        if ($distincts->count() > 1) {
            $this->warn('Le supplément « jour même » varie de ' . formatAmount($ecarts->min())
                . ' à ' . formatAmount($ecarts->max()) . ' selon la tranche.');
            $this->line('Le modèle retenu n\'admet qu\'un supplément global : choisir une valeur');
            $this->line('(médiane : ' . formatAmount($this->mediane($ecarts->all())) . ') et l\'assumer, ou revoir la grille.');
        }

        if (!$this->option('appliquer')) {
            return;
        }

        DB::transaction(function () use ($societe, $lignes) {
            $zones = $this->zones($societe);
            $this->delais($societe);

            $ecrites = 0;
            foreach ($lignes as $ligne) {
                foreach (self::ZONES as [$code, $nom, $colonne]) {
                    $montant = $colonne === null ? 0 : (float) $ligne->{$colonne};

                    // Pas d'`updateOrCreate` : `company_id` n'est pas assignable
                    // en masse sur ces modèles du socle — la ligne serait créée
                    // sans société, et une seconde exécution en créerait une de
                    // plus, indéfiniment.
                    $cible = DeliveryCharge::where('company_id', $societe->id)
                        ->where('category_id', $ligne->category_id)
                        ->where('zone_id', $zones[$code]->id)
                        ->where('weight', $ligne->weight)
                        ->first() ?? new DeliveryCharge();

                    $cible->company_id = $societe->id;
                    $cible->category_id = $ligne->category_id;
                    $cible->zone_id = $zones[$code]->id;
                    $cible->weight = $ligne->weight;
                    $cible->amount = $montant;
                    $cible->position = $ligne->position;
                    $cible->status = Status::ACTIVE;
                    $cible->save();
                    $ecrites++;
                }
            }

            $this->info("{$ecrites} ligne(s) de barème par zone écrites ; les colonnes héritées sont intactes.");
        });

        $this->comment('CEDEAO : forfaits par pays à saisir (table `delivery_zone_countries`).');
    }

    /** @return array<string, DeliveryZone> */
    private function zones(GeneralSettings $societe): array
    {
        $out = [];
        foreach (self::ZONES as $position => [$code, $nom, $colonne]) {
            $zone = DeliveryZone::where('company_id', $societe->id)->where('code', $code)->first() ?? new DeliveryZone();
            $zone->company_id = $societe->id;
            $zone->code = $code;
            $zone->name = $nom;
            $zone->position = $position;
            $zone->status = Status::ACTIVE;
            $zone->save();

            $out[$code] = $zone;
        }

        return $out;
    }

    private function delais(GeneralSettings $societe): void
    {
        $supplement = $this->option('supplement');

        foreach (self::DELAIS as $position => [$code, $nom]) {
            $valeur = $code === DeliveryDelay::SAME_DAY ? (float) ($supplement ?? 0) : 0;

            $delai = DeliveryDelay::where('company_id', $societe->id)->where('code', $code)->first() ?? new DeliveryDelay();
            $delai->company_id = $societe->id;
            $delai->code = $code;
            $delai->name = $nom;
            $delai->surcharge = $valeur;
            $delai->position = $position;
            $delai->status = Status::ACTIVE;
            $delai->save();
        }

        if ($supplement === null) {
            $this->warn('Délais créés à 0 : le supplément « jour même » reste à fixer (--supplement=).');
        }
    }

    /** @param array<int, float> $valeurs */
    private function mediane(array $valeurs): float
    {
        sort($valeurs);
        $n = count($valeurs);

        return $n === 0 ? 0.0 : ($n % 2 ? $valeurs[intdiv($n, 2)] : ($valeurs[$n / 2 - 1] + $valeurs[$n / 2]) / 2);
    }
}
