<?php

namespace App\Services\Pricing;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
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
 *     CEDEAO     ← forfaits par pays, à saisir
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
