/**
 * Colis et boutiques.
 *
 * ⚠️ `POST parcel/store` n'est **pas** exposé ici volontairement. Il exige un
 * champ `chargeDetails` contenant une **chaîne JSON** de montants que le client
 * calcule lui-même (frais, TVA, COD, net) et que le serveur enregistre sans
 * recalcul — constat S2. Vérifié à l'appel : sans ce champ la création échoue
 * silencieusement, et un objet au lieu d'une chaîne provoque une erreur 500.
 * Porter ce calcul dans l'app reconduirait la faille et dupliquerait le barème.
 * ⇒ La création de colis attend que le calcul revienne côté serveur (lot 4).
 */
import { api } from './client';
import { endpoints } from './endpoints';
import type { Parcel, ParcelEvent, ParcelStatusOption, Shop } from './types';

export async function fetchParcels(): Promise<Parcel[]> {
  const data = await api.get<{ parcels: Parcel[] }>(endpoints.parcelIndex);
  return data?.parcels ?? [];
}

export function fetchParcelStatuses(): Promise<ParcelStatusOption[]> {
  // Réponse nue : un tableau, sans enveloppe `data`.
  return api.get<ParcelStatusOption[]>(endpoints.parcelAllStatus);
}

export async function fetchParcelDetails(id: number): Promise<Parcel> {
  const data = await api.get<{ parcel: Parcel } | Parcel>(endpoints.parcelDetails(id));
  return (data as { parcel?: Parcel }).parcel ?? (data as Parcel);
}

/** Suivi d'un colis : le colis et ses événements de statut. */
export async function fetchParcelTimeline(
  id: number,
): Promise<{ parcel: Parcel | null; events: ParcelEvent[] }> {
  const data = await api.get<{ parcel: Parcel; parcelEvents: ParcelEvent[] }>(
    endpoints.parcelLogs(id),
  );
  return { parcel: data?.parcel ?? null, events: data?.parcelEvents ?? [] };
}

export async function fetchShops(): Promise<Shop[]> {
  const data = await api.get<{ shops: Shop[] }>(endpoints.shopsIndex);
  return data?.shops ?? [];
}
