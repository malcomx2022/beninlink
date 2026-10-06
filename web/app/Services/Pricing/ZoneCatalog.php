<?php

namespace App\Services\Pricing;

use App\Enums\Status;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;

/**
 * Le catalogue du modèle par zones (**D4**) : quelles zones existent, quels
 * délais, quels pays d'export, et à quels forfaits.
 *
 * Cette classe s'appelait `ZoneGridConverter` tant qu'elle **convertissait** le
 * barème hérité. L'étape 6 a retiré les quatre colonnes le 2026-09-07 : il n'y
 * a plus rien à convertir, et le nom mentirait. Ce qui reste — et qui n'a
 * jamais dépendu des colonnes — est la **définition** du modèle, en un seul
 * endroit, pour que le jeu d'amorçage, la commande d'installation et les tests
 * ne tiennent pas trois copies qui dériveraient.
 *
 * La correspondance historique reste consignée dans `docs/DECISIONS_METIER.md`
 * (D4) : Cotonou ← `next_day`, Périphérie ← `sub_city`, Intérieur ←
 * `outside_city`, et un supplément « jour même » global de 300 F.
 */
class ZoneCatalog
{
    /** Supplément « jour même » retenu par le métier, en FCFA entiers. */
    public const SAME_DAY_SURCHARGE = 300;

    /** [code, libellé] — l'ordre fixe la position à l'écran. */
    public const ZONES = [
        [DeliveryZone::COTONOU, 'Cotonou'],
        [DeliveryZone::PERIPHERIE, 'Périphérie'],
        [DeliveryZone::INTERIEUR, 'Intérieur'],
        [DeliveryZone::CEDEAO, 'CEDEAO'],
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
        // S106 (R1) — les cinq autres pays desservis, posés comme POINT DE DÉPART
        // (créés s'ils manquent, jamais réécrits) : gradués à la distance et au
        // mode d'acheminement depuis Cotonou, le transporteur les ajuste à l'écran.
        ['GH', 'Ghana', 15000],
        ['NE', 'Niger', 18000],
        ['CI', "Côte d'Ivoire", 20000],
        ['ML', 'Mali', 22000],
        ['SN', 'Sénégal', 25000],
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

    /** @return array<string, DeliveryZone> */
    public function zones(int $companyId): array
    {
        $out = [];
        foreach (self::ZONES as $position => [$code, $nom]) {
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
     * Installe le modèle par zones d'une société : les quatre zones, les trois
     * délais, et les forfaits CEDEAO.
     *
     * C'est ce dont une installation neuve a besoin pour facturer quoi que ce
     * soit — depuis l'étape 6, un colis sans zone n'a pas de tarif. La grille
     * elle-même (tranche × zone) se saisit ensuite à l'écran : ses montants
     * appartiennent au transporteur, pas au logiciel.
     */
    public function installer(int $companyId, float $supplement = self::SAME_DAY_SURCHARGE): array
    {
        $zones = $this->zones($companyId);
        $this->delais($companyId, $supplement);
        $this->pays($zones[DeliveryZone::CEDEAO] ?? null);

        return $zones;
    }
}
