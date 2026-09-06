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
 * Ce qui dépend encore des **quatre colonnes héritées** (**D4**, préalable à
 * l'étape 6).
 *
 * L'étape 6 supprime `same_day`, `next_day`, `sub_city` et `outside_city` des
 * deux tables de barème. Elle est **irréversible** et **sèche** : une fois ces
 * colonnes parties, `ChargeCalculator` n'a plus de repli, et une société qui
 * n'a pas converti son barème ne peut plus créer un colis du tout.
 *
 * Le plan disait « une fois les apps déployées ». C'est une intention, pas une
 * vérification : personne ne pouvait répondre, installation par installation,
 * à la question « est-ce que quelque chose s'appuie encore sur ces colonnes ? ».
 * Cette classe y répond, par société, et nomme chaque blocage.
 *
 * Elle ne corrige rien : c'est un constat. La conversion reste le travail de
 * `beninlink:zones-tarifaires`, qui affiche le tableau des écarts avant
 * d'écrire — un tarif qui se déplace doit être lu, pas subi.
 */
class LegacyGridAudit
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

        // 1. Aucune zone : la société facture entièrement par les colonnes.
        //    C'est le blocage le plus grave, et le plus silencieux à venir —
        //    après l'étape 6, elle ne facturerait plus rien du tout.
        if ($zones->isEmpty()) {
            $blocages[] = 'aucune zone configurée — la société facture encore par les quatre colonnes';

            return compact('societe', 'blocages', 'avertissements') + ['colis_sans_zone' => $this->colisSansZone($societe)];
        }

        $nationales = $zones->reject(fn (DeliveryZone $zone) => $zone->isExport());

        // 2. Tranches héritées sans équivalent zoné : le tarif existerait dans
        //    l'ancien monde et pas dans le nouveau. `resolveByZone()` rendrait
        //    `null`, et la création serait refusée.
        foreach ($this->tranchesHeritees($societe) as $categoryId => $poids) {
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

        // 3. Barèmes négociés restés sur les colonnes : le marchand perdrait
        //    son tarif négocié et retomberait sur celui de la société, sans
        //    que rien ne le dise.
        $negocies = MerchantDeliveryCharge::where('company_id', $societe->id)
            ->whereNull('zone_id')->count();

        if ($negocies > 0) {
            $blocages[] = "{$negocies} barème(s) négocié(s) marchand encore sur les colonnes héritées";
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
     * Le signal le plus honnête sur l'état du parc : tant que des colis
     * arrivent sans zone, un écran ou une app en circulation utilise encore le
     * chemin hérité, quoi que dise le calendrier de déploiement.
     */
    public function colisSansZone(GeneralSettings $societe): int
    {
        return Parcel::where('company_id', $societe->id)
            ->whereNull('zone_id')
            ->where('created_at', '>=', Carbon::now()->subDays(self::FENETRE_JOURS))
            ->count();
    }

    /**
     * Tranches de poids du barème hérité, par catégorie.
     *
     * @return array<int, array<int, int>>
     */
    private function tranchesHeritees(GeneralSettings $societe): array
    {
        $tranches = [];

        foreach (DeliveryCharge::where('company_id', $societe->id)->whereNull('zone_id')->get() as $ligne) {
            $categoryId = (int) $ligne->category_id;
            $tranches[$categoryId][] = (int) $ligne->weight;
        }

        foreach ($tranches as $categoryId => $poids) {
            $tranches[$categoryId] = array_values(array_unique($poids));
        }

        return $tranches;
    }

    /** Une société est prête quand plus rien ne s'appuie sur les colonnes. */
    public function estPrete(array $audit): bool
    {
        return $audit['blocages'] === [] && $audit['colis_sans_zone'] === 0;
    }
}
