/**
 * Types de livraison.
 *
 * Les identifiants viennent de `web/app/Enums/DeliveryType.php` : ils
 * appartiennent au contrat d'API et ne se redéfinissent pas ici.
 *
 * ⚠️ `GET parcel/create` renvoie des `deliveryTypes` qui sont des **interrupteurs
 * de configuration** (`{key, value}`, `value = "1"` = activé) et ne portent aucun
 * identifiant. La correspondance clé → identifiant se fait donc ici.
 *
 * ⚠️ `outside_City` porte une majuscule au milieu — faute de frappe du socle
 * We Courier, conservée telle quelle puisque c'est la clé réellement renvoyée.
 */
export const DELIVERY_TYPE_IDS = {
  same_day: 1,
  next_day: 2,
  sub_city: 3,
  outside_City: 4,
} as const;

export type DeliveryTypeKey = keyof typeof DELIVERY_TYPE_IDS;

/** Libellés français, le backend n'en fournissant pas pour ces clés. */
export const DELIVERY_TYPE_LABELS: Record<DeliveryTypeKey, string> = {
  same_day: 'Le jour même',
  next_day: 'Le lendemain',
  sub_city: 'Sous-ville',
  outside_City: 'Hors de la ville',
};

export function deliveryTypeId(key: string): number | null {
  return (DELIVERY_TYPE_IDS as Record<string, number>)[key] ?? null;
}

export function deliveryTypeLabel(key: string): string {
  return (DELIVERY_TYPE_LABELS as Record<string, string>)[key] ?? key;
}
