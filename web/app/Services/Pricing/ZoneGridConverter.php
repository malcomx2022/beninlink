<?php

namespace App\Services\Pricing;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
use Illuminate\Support\Collection;

/**
 * Passage du barème hérité au barème par zones (**D4**).
 *
 * ## La correspondance, décidée par le métier
 *
 * Les quatre colonnes mélangeaient délai et périmètre. Le métier a tranché le
 * 2026-09-06 : **on garde les tarifs actuels**, ce qui fixe la correspondance.
 *
 *     Cotonou    ← next_day       (l'intra-ville, au délai standard)
 *     Périphérie ← sub_city
 *     Intérieur  ← outside_city
 *     CEDEAO     ← forfaits par pays (Togo 12 000, Nigeria 18 000,
 *                    Burkina Faso 15 000 — tranchés le 2026-09-06)
 *
 * Et un **supplément « jour même » de 300 F**, global — le même quelle que
 * soit la zone. C'est la seule chose que la refonte déplace, et elle le fait
 * sciemment : aujourd'hui ce supplément vaut 200 F sur 1 kg et 500 F sur
 * 10 kg. Un modèle à supplément unique ne peut pas rendre les deux ;
 * `écarts()` dit exactement ce que chaque tranche gagne ou perd.
 *
 * Ce service est le seul endroit qui connaît cette correspondance : la
 * commande `beninlink:zones-tarifaires` et le jeu de recette l'appellent tous
 * les deux.
 */
class ZoneGridConverter
{
    /** Supplément « jour même » retenu par le métier, en FCFA entiers. */
    public const SAME_DAY_SURCHARGE = 300;

    /** [code, libellé, colonne héritée] — `null` = zone sans équivalent. */
    public const ZONES = [
        [DeliveryZone::COTONOU, 'Cotonou', 'next_day'],
        [DeliveryZone::PERIPHERIE, 'Périphérie', 'sub_city'],
        [DeliveryZone::INTERIEUR, 'Intérieur', 'outside_city'],
        [DeliveryZone::CEDEAO, 'CEDEAO', null],
    ];

    public const DELAIS = [
        [DeliveryDelay::SAME_DAY, 'Jour même'],
        [DeliveryDelay::NEXT_DAY, 'Lendemain'],
        [DeliveryDelay::STANDARD, 'Standard'],
    ];

    /**
     * Forfaits CEDEAO, tranchés par le métier le 2026-09-06 : **[code, pays, forfait]**.
     *
     * Un envoi vers la CEDEAO se facture au **pays**, forfait, sans regarder le
     * poids — c'était la décision ; il manquait les montants. Les voici.
     *
     * ⚠️ Ces lignes sont **créées si elles manquent, jamais réécrites**. Le
     * supplément de délai, lui, se réécrit à chaque passage parce que la
     * commande le prend en option (`--supplement`) : c'est une valeur du run.
     * Un forfait de pays n'a pas d'équivalent — un transporteur qui l'a ajusté
     * dans l'écran de saisie ne doit pas le voir revenir à la valeur d'usine
     * parce qu'on a relancé la conversion.
     */
    public const PAYS = [
        ['TG', 'Togo', 12000],
        ['NG', 'Nigeria', 18000],
        ['BF', 'Burkina Faso', 15000],
    ];

    /**
     * Forfaits de la zone d'export — **créés s'ils manquent, jamais réécrits**.
     *
     * Voir `PAYS` : un montant ajusté à l'écran de saisie doit survivre à une
     * relance de la conversion.
     *
     * @return int nombre de pays créés
     */
    public function pays(?DeliveryZone $cedeao): int
    {
        if ($cedeao === null) {
            return 0;
        }

        $crees = 0;
        foreach (self::PAYS as [$code, $nom, $forfait]) {
            if (DeliveryZoneCountry::where('zone_id', $cedeao->id)->where('code', $code)->exists()) {
                continue;
            }

            $ligne = new DeliveryZoneCountry();
            $ligne->zone_id = $cedeao->id;
            $ligne->code = $code;
            $ligne->name = $nom;
            $ligne->flat_amount = $forfait;
            $ligne->status = Status::ACTIVE;
            $ligne->save();
            $crees++;
        }

        return $crees;
    }

    /** Lignes héritées d'une société : celles qui n'ont pas encore de zone. */
    public function legacyRows(int $companyId): Collection
    {
        return DeliveryCharge::where('company_id', $companyId)->whereNull('zone_id')
            ->orderBy('category_id')->orderBy('weight')->get();
    }

    /**
     * Ce que le supplément unique change, tranche par tranche.
     *
     * @return array<int, array{weight:int, actuel:float, retenu:float, ecart:float}>
     */
    public function ecarts(int $companyId, float $supplement): array
    {
        return $this->legacyRows($companyId)->map(fn (DeliveryCharge $ligne) => [
            'weight' => (int) $ligne->weight,
            'actuel' => (float) $ligne->same_day - (float) $ligne->next_day,
            'retenu' => $supplement,
            'ecart' => $supplement - ((float) $ligne->same_day - (float) $ligne->next_day),
        ])->all();
    }

    /** @return array<string, DeliveryZone> */
    public function zones(int $companyId): array
    {
        $out = [];
        foreach (self::ZONES as $position => [$code, $nom, $colonne]) {
            // Pas d'`updateOrCreate` : `company_id` n'est pas assignable en
            // masse sur ces modèles du socle — la ligne partirait sans société,
            // et une seconde exécution en créerait une de plus.
            $zone = DeliveryZone::where('company_id', $companyId)->where('code', $code)->first() ?? new DeliveryZone();
            $zone->company_id = $companyId;
            $zone->code = $code;
            $zone->name = $nom;
            $zone->position = $position;
            $zone->status = Status::ACTIVE;
            $zone->save();

            $out[$code] = $zone;
        }

        return $out;
    }

    public function delais(int $companyId, float $supplement): void
    {
        foreach (self::DELAIS as $position => [$code, $nom]) {
            $delai = DeliveryDelay::where('company_id', $companyId)->where('code', $code)->first() ?? new DeliveryDelay();
            $delai->company_id = $companyId;
            $delai->code = $code;
            $delai->name = $nom;
            $delai->surcharge = $code === DeliveryDelay::SAME_DAY ? $supplement : 0;
            $delai->position = $position;
            $delai->status = Status::ACTIVE;
            $delai->save();
        }
    }

    /**
     * Écrit une ligne de barème par zone, aux montants d'aujourd'hui.
     *
     * @return int nombre de lignes écrites
     */
    public function convert(int $companyId, float $supplement): int
    {
        $zones = $this->zones($companyId);
        $this->delais($companyId, $supplement);
        $this->pays($zones[DeliveryZone::CEDEAO] ?? null);

        $ecrites = 0;
        foreach ($this->legacyRows($companyId) as $ligne) {
            foreach (self::ZONES as [$code, $nom, $colonne]) {
                $cible = DeliveryCharge::where('company_id', $companyId)
                    ->where('category_id', $ligne->category_id)
                    ->where('zone_id', $zones[$code]->id)
                    ->where('weight', $ligne->weight)
                    ->first() ?? new DeliveryCharge();

                $cible->company_id = $companyId;
                $cible->category_id = $ligne->category_id;
                $cible->zone_id = $zones[$code]->id;
                $cible->weight = $ligne->weight;
                $cible->amount = $colonne === null ? 0 : (float) $ligne->{$colonne};
                $cible->position = $ligne->position;
                $cible->status = Status::ACTIVE;
                $cible->save();
                $ecrites++;
            }
        }

        return $ecrites;
    }
}
