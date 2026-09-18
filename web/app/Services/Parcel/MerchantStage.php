<?php

namespace App\Services\Parcel;

use App\Enums\ParcelStatus;

/**
 * Les 33 statuts du socle, ramenés aux **7 étapes marchand** — et à leur
 * pastille de charte.
 *
 * Port PHP de `mobile/src/domain/parcelStatus.ts`. C'est la même table, et elle
 * doit le rester : le back-office et l'app montrent le même colis, et rien ne
 * serait plus déroutant qu'une lecture par support. Un test compare les deux
 * fichiers ligne à ligne.
 *
 * ⚠️ Les **valeurs numériques appartiennent au contrat d'API** : ce service ne
 * les redéfinit pas, il les lit dans `ParcelStatus`. Et il ne touche à aucun
 * **libellé** : ceux-ci viennent de `lang/fr/parcelStatus.php`. On aligne ici
 * les couleurs et les regroupements, jamais les textes.
 *
 * ⚠️ Fichier **ajouté**, hors du socle : il traverse une montée de version de
 * We Courier sans conflit (voir `docs/guides/socle/mise-a-jour-we-courier.md`).
 */
final class MerchantStage
{
    public const PENDING = 'pending';
    public const PICKUP_ASSIGNED = 'pickup_assigned';
    public const WAREHOUSE = 'warehouse';
    public const COURIER_ASSIGNED = 'courier_assigned';
    public const DELIVERED = 'delivered';
    public const PARTIAL = 'partial';
    public const RETURNED = 'returned';

    /**
     * 33 → 7.
     *
     * Un statut `*_CANCEL` **annule une transition** : le colis revient à
     * l'étape précédente, il ne prend pas d'étape « annulé » à lui. C'est
     * pourquoi chacun est rattaché à l'étape amont — et non à une huitième
     * famille qui n'existe dans aucun des deux supports.
     *
     * @return array<int, string>
     */
    public static function table(): array
    {
        return [
            ParcelStatus::PENDING => self::PENDING,
            ParcelStatus::PICKUP_ASSIGN_CANCEL => self::PENDING,

            ParcelStatus::PICKUP_ASSIGN => self::PICKUP_ASSIGNED,
            ParcelStatus::PICKUP_RE_SCHEDULE => self::PICKUP_ASSIGNED,
            ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL => self::PICKUP_ASSIGNED,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN => self::PICKUP_ASSIGNED,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL => self::PICKUP_ASSIGNED,

            ParcelStatus::RECEIVED_WAREHOUSE => self::WAREHOUSE,
            ParcelStatus::RECEIVED_WAREHOUSE_CANCEL => self::WAREHOUSE,
            ParcelStatus::TRANSFER_TO_HUB => self::WAREHOUSE,
            ParcelStatus::TRANSFER_TO_HUB_CANCEL => self::WAREHOUSE,
            ParcelStatus::RECEIVED_BY_HUB => self::WAREHOUSE,
            ParcelStatus::RECEIVED_BY_HUB_CANCEL => self::WAREHOUSE,

            ParcelStatus::DELIVERY_MAN_ASSIGN => self::COURIER_ASSIGNED,
            ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL => self::COURIER_ASSIGNED,
            ParcelStatus::DELIVERY_RE_SCHEDULE => self::COURIER_ASSIGNED,
            ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL => self::COURIER_ASSIGNED,

            ParcelStatus::DELIVERED => self::DELIVERED,
            ParcelStatus::DELIVER => self::DELIVERED,
            ParcelStatus::DELIVERED_CANCEL => self::COURIER_ASSIGNED,

            ParcelStatus::PARTIAL_DELIVERED => self::PARTIAL,
            ParcelStatus::PARTIAL_DELIVERED_CANCEL => self::COURIER_ASSIGNED,

            ParcelStatus::RETURN_WAREHOUSE => self::RETURNED,
            ParcelStatus::ASSIGN_MERCHANT => self::RETURNED,
            ParcelStatus::RETURNED_MERCHANT => self::RETURNED,
            ParcelStatus::RETURN_TO_COURIER => self::RETURNED,
            ParcelStatus::RETURN_TO_COURIER_CANCEL => self::RETURNED,
            ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => self::RETURNED,
            ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL => self::RETURNED,
            ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => self::RETURNED,
            ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL => self::RETURNED,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => self::RETURNED,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL => self::RETURNED,
        ];
    }

    /**
     * Étape marchand d'un code backend.
     *
     * Le repli sur `pending` est **le même que celui de l'app** — et c'est lui
     * qui ferme la fragilité relevée au §2.6 de l'audit : `StatusParcel()`
     * n'avait pas de `else`, si bien qu'un code hors liste rendait une cellule
     * vide et un `Warning: Undefined variable`.
     */
    public static function for($code): string
    {
        return self::table()[(int) $code] ?? self::PENDING;
    }

    /**
     * Famille de pastille d'une étape (`tokens.css`, § 5).
     *
     * Les six premières sont les étapes de la timeline, dessinées par la
     * maquette. La septième — `partial` — est un **incident** : la charte lui
     * donne l'avertissement, donc l'orange.
     */
    public static function pill($code): string
    {
        return [
            self::PENDING => 'wait',
            self::PICKUP_ASSIGNED => 'transit',
            self::WAREHOUSE => 'hub',
            self::COURIER_ASSIGNED => 'assign',
            self::DELIVERED => 'done',
            self::PARTIAL => 'partial',
            self::RETURNED => 'return',
        ][self::for($code)];
    }

    /** Hors parcours nominal : montré comme incident, pas dans la timeline. */
    public static function isIncident($code): bool
    {
        return in_array(self::for($code), [self::PARTIAL, self::RETURNED], true);
    }

    /** Ordre de la timeline de suivi — identique à `TIMELINE_ORDER` de l'app. */
    public static function timeline(): array
    {
        return [self::PENDING, self::PICKUP_ASSIGNED, self::WAREHOUSE, self::COURIER_ASSIGNED, self::DELIVERED];
    }
}
