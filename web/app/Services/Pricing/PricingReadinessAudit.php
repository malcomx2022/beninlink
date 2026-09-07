<?php

namespace App\Services\Pricing;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Parcel;
use Illuminate\Support\Carbon;

/**
 * **Une société peut-elle facturer ?** (**D4**)
 *
 * Cette classe est née comme la porte de l'étape 6 : elle disait ce qui
 * dépendait encore des quatre colonnes héritées avant qu'on ne les retire. Les
 * colonnes sont parties le 2026-09-07, et ses règles n'ont pas perdu leur
 * objet — elles ont changé de sens.
 *
 * Depuis l'étape 6, il n'existe qu'un seul axe de tarification : la route. Une
 * ligne de barème sans zone, un barème négocié sans zone, un colis créé sans
 * zone ne sont plus « l'ancien monde » : ce sont des **impasses**. Une société
 * sans zone ne facture plus rien du tout, et le découvre au premier colis.
 *
 * L'audit répond donc à une question devenue permanente, société par société,
 * et nomme chaque blocage. Il ne corrige rien : l'installation des zones est le
 * travail de `beninlink:zones-tarifaires`, la grille se saisit à l'écran.
 */
class PricingReadinessAudit
{
    /** Fenêtre d'observation des créations récentes, en jours. */
    public const FENETRE_JOURS = 30;

    /**
     * @return array<int, array{societe:GeneralSettings, blocages:array<int, string>, colis_sans_zone:int, avertissements:array<int, string>}>
     */
    public function auditer(?int $companyId = null): array
    {
        $societes = $companyId === null
            ? GeneralSettings::orderBy('id')->get()
            : GeneralSettings::where('id', $companyId)->get();

        return $societes->map(fn (GeneralSettings $societe) => $this->auditerSociete($societe))->all();
    }

    /**
     * @return array{societe:GeneralSettings, blocages:array<int, string>, colis_sans_zone:int, avertissements:array<int, string>}
     */
    public function auditerSociete(GeneralSettings $societe): array
    {
        $blocages = [];
        $avertissements = [];

        $zones = DeliveryZone::where('company_id', $societe->id)
            ->where('status', Status::ACTIVE)->get();

        // 1. Aucune zone : la société ne peut plus facturer un seul colis.
        //    C'est le blocage le plus grave, et le plus silencieux : rien ne
        //    le dit avant la première création refusée.
        if ($zones->isEmpty()) {
            $blocages[] = 'aucune zone configurée — la société ne peut facturer aucun colis';

            return compact('societe', 'blocages', 'avertissements') + ['colis_sans_zone' => $this->colisSansZone($societe)];
        }

        $nationales = $zones->reject(fn (DeliveryZone $zone) => $zone->isExport());

        // 2. Une tranche tarifée dans une zone et pas dans une autre.
        //
        //    Tant que les quatre colonnes existaient, cette règle se lisait par
        //    comparaison : chaque tranche héritée devait avoir son équivalent
        //    zoné. Les colonnes parties, le référentiel a disparu — mais pas le
        //    défaut. Une catégorie tarifée à Cotonou et pas à l'Intérieur laisse
        //    un trou, et c'est le marchand qui le trouve : `resolveByZone()`
        //    rend `null`, et la création est refusée.
        //
        //    Le référentiel devient donc la grille elle-même : ce qui est
        //    tarifé quelque part doit l'être partout.
        foreach ($this->tranchesParCategorie($societe, $nationales) as $categoryId => $poids) {
            foreach ($nationales as $zone) {
                $manquantes = array_values(array_filter(
                    $poids,
                    fn (int $p) => !DeliveryCharge::where('company_id', $societe->id)
                        ->where('category_id', $categoryId)
                        ->where('zone_id', $zone->id)
                        ->where('weight', $p)
                        ->exists()
                ));

                if ($manquantes !== []) {
                    $blocages[] = sprintf(
                        'catégorie %d, zone « %s » : tranche(s) %s sans tarif zoné',
                        $categoryId,
                        $zone->name,
                        implode(', ', $manquantes)
                    );
                }
            }
        }

        // 3. Barèmes négociés sans zone : le marchand a perdu son tarif
        //    négocié et retombe sur celui de la société, sans que rien ne le
        //    dise.
        $negocies = MerchantDeliveryCharge::where('company_id', $societe->id)
            ->whereNull('zone_id')->count();

        if ($negocies > 0) {
            $blocages[] = "{$negocies} barème(s) négocié(s) marchand sans zone";
        }

        // 4. Zone d'export sans forfait : elle existe mais ne peut rien
        //    facturer. Ce n'est pas un blocage pour l'étape 6 — la zone
        //    n'était pas tarifée avant non plus — mais il faut le voir.
        foreach ($zones->filter(fn (DeliveryZone $zone) => $zone->isExport()) as $zone) {
            if ($zone->countries()->count() === 0) {
                $avertissements[] = "zone « {$zone->name} » : aucun pays tarifé, elle refusera toute création";
            }
        }

        return compact('societe', 'blocages', 'avertissements') + ['colis_sans_zone' => $this->colisSansZone($societe)];
    }

    /**
     * Colis créés **sans zone** sur la fenêtre récente.
     *
     * Le signal le plus honnête sur l'état du parc : un colis sans zone ne
     * devrait plus pouvoir naître. S'il en arrive, un chemin de création
     * contourne le calculateur — c'est un défaut, pas un retard.
     */
    public function colisSansZone(GeneralSettings $societe): int
    {
        return Parcel::where('company_id', $societe->id)
            ->whereNull('zone_id')
            ->where('created_at', '>=', Carbon::now()->subDays(self::FENETRE_JOURS))
            ->count();
    }

    /**
     * Tranches de poids tarifées, par catégorie, toutes zones nationales
     * confondues — le référentiel de complétude de la grille.
     *
     * @param \Illuminate\Support\Collection<int, DeliveryZone> $nationales
     * @return array<int, array<int, int>>
     */
    private function tranchesParCategorie(GeneralSettings $societe, $nationales): array
    {
        $tranches = [];

        $lignes = DeliveryCharge::where('company_id', $societe->id)
            ->whereIn('zone_id', $nationales->pluck('id'))->get();

        foreach ($lignes as $ligne) {
            $tranches[(int) $ligne->category_id][] = (int) $ligne->weight;
        }

        foreach ($tranches as $categoryId => $poids) {
            $tranches[$categoryId] = array_values(array_unique($poids));
        }

        return $tranches;
    }

    /** Une société est prête quand tout ce qui tarife porte une zone. */
    public function estPrete(array $audit): bool
    {
        return $audit['blocages'] === [] && $audit['colis_sans_zone'] === 0;
    }
}
