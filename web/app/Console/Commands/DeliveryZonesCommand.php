<?php

namespace App\Console\Commands;

use App\Models\Backend\GeneralSettings;
use App\Services\Pricing\ZoneGridConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan beninlink:zones-tarifaires` — passer le barème aux zones (**D4**).
 *
 * ## Ce que le métier a tranché (2026-09-06)
 *
 *   - zones **Cotonou, Périphérie, Intérieur, CEDEAO** ;
 *   - **délai global** : un supplément par délai, indépendant de la zone ;
 *   - **CEDEAO au forfait par pays**, pas au poids ;
 *   - **on garde les tarifs actuels**, avec un supplément « jour même » de
 *     **300 F**.
 *
 * La correspondance et l'écriture vivent dans `ZoneGridConverter` — le jeu de
 * recette s'en sert aussi. Ici, seulement le dialogue : constater, montrer ce
 * que le supplément unique déplace, puis écrire sur demande.
 */
class DeliveryZonesCommand extends Command
{
    protected $signature = 'beninlink:zones-tarifaires
        {--societe= : société à traiter ; par défaut toutes}
        {--supplement= : supplément « jour même », en FCFA entiers (défaut : 300, décision du métier)}
        {--appliquer : écrire les zones, les délais et le barème converti}';

    protected $description = 'Convertit le barème hérité en zones (D4) : constate, puis écrit sur demande';

    public function handle(ZoneGridConverter $converter): int
    {
        $supplement = (float) ($this->option('supplement') ?? ZoneGridConverter::SAME_DAY_SURCHARGE);

        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::all();

        if ($societes->isEmpty()) {
            $this->error('Aucune société.');

            return self::FAILURE;
        }

        foreach ($societes as $societe) {
            $this->traiter($converter, $societe, $supplement);
        }

        if (!$this->option('appliquer')) {
            $this->newLine();
            $this->comment('Constat seul. Ajouter --appliquer pour écrire.');
        }

        return self::SUCCESS;
    }

    private function traiter(ZoneGridConverter $converter, GeneralSettings $societe, float $supplement): void
    {
        $lignes = $converter->legacyRows($societe->id);

        $this->newLine();
        $this->line("<options=bold>{$societe->name}</> — {$lignes->count()} ligne(s) héritée(s)");

        if ($lignes->isEmpty()) {
            return;
        }

        $this->table(
            ['Tranche', 'Cotonou', 'Périphérie', 'Intérieur', '« Jour même » aujourd\'hui', 'Retenu', 'Écart'],
            collect($converter->ecarts($societe->id, $supplement))
                ->zip($lignes)
                ->map(function ($paire) {
                    [$ecart, $ligne] = $paire;

                    return [
                        $ecart['weight'] . ' kg',
                        formatAmount((float) $ligne->next_day),
                        formatAmount((float) $ligne->sub_city),
                        formatAmount((float) $ligne->outside_city),
                        formatAmount($ecart['actuel']),
                        formatAmount($ecart['retenu']),
                        ($ecart['ecart'] > 0 ? '+' : '') . formatAmount($ecart['ecart']),
                    ];
                })->all(),
        );

        $deplaces = collect($converter->ecarts($societe->id, $supplement))->filter(fn (array $e) => abs($e['ecart']) > 0.001);
        if ($deplaces->isNotEmpty()) {
            $this->warn("Le supplément unique déplace le tarif « jour même » de {$deplaces->count()} tranche(s).");
            $this->line('Les autres tarifs (Cotonou, Périphérie, Intérieur, tous délais confondus) sont inchangés.');
        }

        if (!$this->option('appliquer')) {
            return;
        }

        $ecrites = DB::transaction(fn () => $converter->convert($societe->id, $supplement));

        $this->info("{$ecrites} ligne(s) de barème par zone écrites ; les colonnes héritées sont intactes.");
        $this->comment('CEDEAO : forfaits par pays à saisir (table `delivery_zone_countries`).');
    }
}
