<?php

namespace App\Services\Parcel;

use App\Enums\ParcelStatus;

/**
 * Les 33 codes de statut du socle, ramenés aux 7 étapes que l'humain lit.
 *
 * ┌─ POURQUOI CETTE CLASSE ────────────────────────────────────────────────────┐
 * │ Le socle peignait les statuts à DEUX endroits, en double, avec la même      │
 * │ table fautive : `StatusParcel()` (Helper.php) et                            │
 * │ `Parcel::getParcelStatusAttribute()`. Les deux couvraient 19 codes sur 33,  │
 * │ sans `else` et sans initialiser leur variable — un code non listé donnait   │
 * │ « Undefined variable » et une cellule vide. Les deux délèguent désormais     │
 * │ ici : la table existe une fois, et le repli existe.                          │
 * └────────────────────────────────────────────────────────────────────────────┘
 *
 * **Portage de `mobile/src/domain/parcelStatus.ts`.** L'app marchand fait déjà
 * cette réduction, et sa table est la référence : un marchand qui regarde son
 * téléphone puis le back-office doit voir le même colis au même stade. Les deux
 * tables doivent rester d'accord — `ParcelStageTest` les compare code par code.
 *
 * ⚠️ **Les valeurs numériques ne se redéfinissent jamais ici** : elles
 * appartiennent au contrat d'API que les deux apps consomment (règle d'or —
 * `web/` est le contrat). On regroupe et on colore, on ne renomme rien.
 *
 * ⚠️ Les libellés viennent de `lang/fr/parcelStatus.php`, déjà traduits par le
 * socle. Cette classe n'en écrit aucun.
 */
final class ParcelStage
{
    /** En attente — le colis est créé, rien n'a bougé. Neutre, PAS rouge. */
    public const WAIT = 'wait';

    /** Ramassage assigné — un livreur va le chercher. */
    public const TRANSIT = 'transit';

    /** Entrepôt / hub — il est chez le transporteur. */
    public const HUB = 'hub';

    /** Livreur assigné — il est en tournée de livraison. */
    public const ASSIGN = 'assign';

    /** Livré. C'est le seul stade qui mérite le vert. */
    public const DONE = 'done';

    /** Livraison partielle — incident : le COD encaissé ne suit pas la commande. */
    public const PARTIAL = 'partial';

    /**
     * Retour, sous toutes ses formes — incident.
     * Le nom de la constante est `RETURNED` (`return` est un mot réservé) ;
     * sa valeur reste `return`, qui est le suffixe de la classe CSS.
     */
    public const RETURNED = 'return';

    /**
     * Code du backend → étape lisible.
     *
     * Les statuts `_CANCEL` annulent une transition : le colis **revient à
     * l'étape précédente**, il ne prend pas d'étape « annulé » propre. C'est
     * pourquoi ils sont rattachés à l'étape amont — et c'est aussi ce qui, en
     * pratique, ferme les 14 codes que le socle ne couvrait pas.
     *
     * Deux rattachements méritent l'attention, car ils ne sont pas symétriques :
     * annuler une livraison (`DELIVERED_CANCEL`) ou une livraison partielle
     * (`PARTIAL_DELIVERED_CANCEL`) ramène le colis **au livreur**, pas à
     * l'entrepôt — c'est ce que fait la route d'annulation du socle.
     */
    private const BY_CODE = [
        ParcelStatus::PENDING => self::WAIT,
        ParcelStatus::PICKUP_ASSIGN_CANCEL => self::WAIT,

        ParcelStatus::PICKUP_ASSIGN => self::TRANSIT,
        ParcelStatus::PICKUP_RE_SCHEDULE => self::TRANSIT,
        ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL => self::TRANSIT,
        ParcelStatus::RECEIVED_BY_PICKUP_MAN => self::TRANSIT,
        ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL => self::TRANSIT,

        ParcelStatus::RECEIVED_WAREHOUSE => self::HUB,
        ParcelStatus::RECEIVED_WAREHOUSE_CANCEL => self::HUB,
        ParcelStatus::TRANSFER_TO_HUB => self::HUB,
        ParcelStatus::TRANSFER_TO_HUB_CANCEL => self::HUB,
        ParcelStatus::RECEIVED_BY_HUB => self::HUB,
        ParcelStatus::RECEIVED_BY_HUB_CANCEL => self::HUB,

        ParcelStatus::DELIVERY_MAN_ASSIGN => self::ASSIGN,
        ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL => self::ASSIGN,
        ParcelStatus::DELIVERY_RE_SCHEDULE => self::ASSIGN,
        ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL => self::ASSIGN,
        ParcelStatus::DELIVERED_CANCEL => self::ASSIGN,
        ParcelStatus::PARTIAL_DELIVERED_CANCEL => self::ASSIGN,

        ParcelStatus::DELIVERED => self::DONE,
        ParcelStatus::DELIVER => self::DONE,

        ParcelStatus::PARTIAL_DELIVERED => self::PARTIAL,

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

    /**
     * Étape d'un code. Un code inconnu retombe sur `WAIT` — comme l'app le fait
     * (`toMerchantStage` : `?? 'pending'`).
     *
     * Le repli est volontairement le **neutre** : si l'éditeur ajoute un statut,
     * mieux vaut une pastille grise qu'un vert ou un rouge inventé. Le libellé,
     * lui, reste celui du backend : l'opérateur lit toujours le vrai statut.
     */
    public static function of(mixed $code): string
    {
        return self::BY_CODE[(int) $code] ?? self::WAIT;
    }

    /** Hors parcours nominal : à traiter, pas seulement à constater. */
    public static function isIncident(mixed $code): bool
    {
        $etape = self::of($code);

        return $etape === self::PARTIAL || $etape === self::RETURNED;
    }

    /**
     * La pastille prête à afficher. Le libellé vient du backend et il est
     * échappé : la sortie part dans un `{!! !!}`, donc rien ne doit pouvoir s'y
     * glisser depuis un fichier de langue.
     */
    public static function pill(mixed $code): string
    {
        $etape = self::of($code);
        $libelle = e(trans('parcelStatus.' . (int) $code));

        return '<span class="bl-pill bl-pill--' . $etape . '">' . $libelle . '</span>';
    }

    /** Les sept étapes, dans l'ordre du parcours. Sert aux tests et aux filtres. */
    public static function families(): array
    {
        return [
            self::WAIT, self::TRANSIT, self::HUB, self::ASSIGN,
            self::DONE, self::PARTIAL, self::RETURNED,
        ];
    }

    /** La table entière, pour qui doit la vérifier. */
    public static function map(): array
    {
        return self::BY_CODE;
    }
}
