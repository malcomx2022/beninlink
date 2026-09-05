/**
 * Statuts de colis.
 *
 * Le backend We Courier expose **33 constantes** (`web/app/Enums/ParcelStatus.php`),
 * chaque transition ayant son pendant `_CANCEL`. La maquette validée n'en présente
 * que **7**. Ce fichier est l'unique table de correspondance : les écrans ne
 * manipulent que les 7 étapes marchand.
 *
 * ⚠️ Ne jamais réinventer les libellés : `GET /api/v10/parcel/all/status` renvoie
 * ceux du backend, déjà traduits. Les libellés ci-dessous ne servent qu'au repli
 * hors ligne et aux regroupements que le backend n'expose pas.
 *
 * ⚠️ Les valeurs numériques appartiennent au contrat d'API : elles ne se
 * redéfinissent pas côté app (règle d'or — `web/` est le contrat).
 */

/** Constantes brutes du backend, à l'identique. */
export const BackendParcelStatus = {
  PENDING: 1,
  PICKUP_ASSIGN: 2,
  PICKUP_RE_SCHEDULE: 3,
  RECEIVED_BY_PICKUP_MAN: 4,
  RECEIVED_WAREHOUSE: 5,
  TRANSFER_TO_HUB: 6,
  DELIVERY_MAN_ASSIGN: 7,
  DELIVERY_RE_SCHEDULE: 8,
  DELIVERED: 9,
  DELIVER: 10,
  RETURN_WAREHOUSE: 11,
  ASSIGN_MERCHANT: 12,
  RETURNED_MERCHANT: 13,
  PICKUP_ASSIGN_CANCEL: 14,
  RECEIVED_BY_PICKUP_MAN_CANCEL: 15,
  RECEIVED_WAREHOUSE_CANCEL: 16,
  DELIVERY_MAN_ASSIGN_CANCEL: 17,
  DELIVERY_RE_SCHEDULE_CANCEL: 18,
  RECEIVED_BY_HUB: 19,
  TRANSFER_TO_HUB_CANCEL: 20,
  RECEIVED_BY_HUB_CANCEL: 21,
  DELIVERED_CANCEL: 22,
  PICKUP_RE_SCHEDULE_CANCEL: 23,
  RETURN_TO_COURIER: 24,
  RETURN_TO_COURIER_CANCEL: 25,
  RETURN_ASSIGN_TO_MERCHANT: 26,
  RETURN_MERCHANT_RE_SCHEDULE: 27,
  RETURN_MERCHANT_RE_SCHEDULE_CANCEL: 28,
  RETURN_ASSIGN_TO_MERCHANT_CANCEL: 29,
  RETURN_RECEIVED_BY_MERCHANT: 30,
  RETURN_RECEIVED_BY_MERCHANT_CANCEL: 31,
  PARTIAL_DELIVERED: 32,
  PARTIAL_DELIVERED_CANCEL: 33,
} as const;

export type BackendStatusCode =
  (typeof BackendParcelStatus)[keyof typeof BackendParcelStatus];

/** Les 7 étapes présentées au marchand (CLAUDE.md racine + maquette). */
export type MerchantStage =
  | 'pending'
  | 'pickup_assigned'
  | 'warehouse'
  | 'courier_assigned'
  | 'delivered'
  | 'partial'
  | 'returned';

const B = BackendParcelStatus;

/**
 * 33 → 7. Les statuts `_CANCEL` annulent une transition : le colis **revient à
 * l'étape précédente**, il ne prend pas d'étape « annulé » propre. C'est pourquoi
 * ils sont rattachés à l'étape amont et non à un statut distinct.
 */
const STAGE_BY_CODE: Record<number, MerchantStage> = {
  [B.PENDING]: 'pending',
  [B.PICKUP_ASSIGN_CANCEL]: 'pending',
  [B.PICKUP_ASSIGN]: 'pickup_assigned',
  [B.PICKUP_RE_SCHEDULE]: 'pickup_assigned',
  [B.PICKUP_RE_SCHEDULE_CANCEL]: 'pickup_assigned',
  [B.RECEIVED_BY_PICKUP_MAN]: 'pickup_assigned',
  [B.RECEIVED_BY_PICKUP_MAN_CANCEL]: 'pickup_assigned',

  [B.RECEIVED_WAREHOUSE]: 'warehouse',
  [B.RECEIVED_WAREHOUSE_CANCEL]: 'warehouse',
  [B.TRANSFER_TO_HUB]: 'warehouse',
  [B.TRANSFER_TO_HUB_CANCEL]: 'warehouse',
  [B.RECEIVED_BY_HUB]: 'warehouse',
  [B.RECEIVED_BY_HUB_CANCEL]: 'warehouse',

  [B.DELIVERY_MAN_ASSIGN]: 'courier_assigned',
  [B.DELIVERY_MAN_ASSIGN_CANCEL]: 'courier_assigned',
  [B.DELIVERY_RE_SCHEDULE]: 'courier_assigned',
  [B.DELIVERY_RE_SCHEDULE_CANCEL]: 'courier_assigned',

  [B.DELIVERED]: 'delivered',
  [B.DELIVER]: 'delivered',
  [B.DELIVERED_CANCEL]: 'courier_assigned',

  [B.PARTIAL_DELIVERED]: 'partial',
  [B.PARTIAL_DELIVERED_CANCEL]: 'courier_assigned',

  [B.RETURN_WAREHOUSE]: 'returned',
  [B.ASSIGN_MERCHANT]: 'returned',
  [B.RETURNED_MERCHANT]: 'returned',
  [B.RETURN_TO_COURIER]: 'returned',
  [B.RETURN_TO_COURIER_CANCEL]: 'returned',
  [B.RETURN_ASSIGN_TO_MERCHANT]: 'returned',
  [B.RETURN_ASSIGN_TO_MERCHANT_CANCEL]: 'returned',
  [B.RETURN_MERCHANT_RE_SCHEDULE]: 'returned',
  [B.RETURN_MERCHANT_RE_SCHEDULE_CANCEL]: 'returned',
  [B.RETURN_RECEIVED_BY_MERCHANT]: 'returned',
  [B.RETURN_RECEIVED_BY_MERCHANT_CANCEL]: 'returned',
};

/** Étape marchand d'un code backend ; `pending` par défaut si le code est inconnu. */
export function toMerchantStage(code: unknown): MerchantStage {
  const n = typeof code === 'number' ? code : Number(code);
  return STAGE_BY_CODE[n] ?? 'pending';
}

/** Ordre de la timeline de suivi (écran parcel-detail). */
export const TIMELINE_ORDER: readonly MerchantStage[] = [
  'pending',
  'pickup_assigned',
  'warehouse',
  'courier_assigned',
  'delivered',
];

/** Étapes hors parcours nominal : affichées comme incident, pas dans la timeline. */
export function isIncident(stage: MerchantStage): boolean {
  return stage === 'partial' || stage === 'returned';
}

/** Regroupements des onglets de la liste de colis (maquette : parcels). */
export const TAB_STAGES: Record<'ongoing' | 'delivered' | 'returns', readonly MerchantStage[]> = {
  ongoing: ['pending', 'pickup_assigned', 'warehouse', 'courier_assigned'],
  delivered: ['delivered', 'partial'],
  returns: ['returned'],
};
