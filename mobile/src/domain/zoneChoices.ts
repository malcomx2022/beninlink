import type { DeliveryZone } from '../api/types';

/**
 * Les choix de zone d'un formulaire de colis — une entrée par zone servie, rien
 * d'autre (**S91 / M4**).
 *
 * Jusqu'à S91 la liste commençait par « Barème hérité (par type de livraison) »,
 * valeur 0, du temps où le serveur servait les deux formes (D4, transition).
 * Depuis l'étape 6 la route est le seul axe de tarification : `zone_id` est
 * obligatoire, et cette entrée ne menait plus qu'à un refus du serveur.
 */
export function zoneChoices(zones: readonly DeliveryZone[]): { value: number; label: string }[] {
  return zones.map((zone) => ({ value: zone.id, label: zone.name }));
}
