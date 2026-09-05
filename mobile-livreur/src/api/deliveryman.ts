/**
 * Espace livreur : courses, statuts, gains, profil.
 *
 * Aucun montant n'est calculé ici : livré / partiel / retour ne font que
 * transmettre l'action et, pour la livraison partielle, le montant réellement
 * encaissé ; le backend recalcule frais, TVA et net (S2).
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { BackendParcelStatus } from '../domain/parcelStatus';
import type {
  DashboardData,
  IncomeExpenseData,
  ParcelDetails,
  ParcelEvent,
  ParcelPaymentLog,
  ProfileData,
} from './types';

export async function fetchDashboard(): Promise<DashboardData> {
  const data = await api.get<Partial<DashboardData>>(endpoints.dashboard);
  return {
    deliveryman_assign: data?.deliveryman_assign ?? [],
    deliveryman_re_schedule: data?.deliveryman_re_schedule ?? [],
    return_to_courier: data?.return_to_courier ?? [],
    delivered: data?.delivered ?? [],
  };
}

export function fetchProfile(): Promise<ProfileData> {
  return api.get<ProfileData>(endpoints.profile);
}

/** Détail d'une course confiée au livreur (404 sinon — S7). */
export async function fetchParcelDetails(
  id: number,
): Promise<{ parcel: ParcelDetails | null; events: ParcelEvent[] }> {
  const data = await api.get<{ parcel: ParcelDetails | null; parcelEvents: ParcelEvent[] }>(
    endpoints.parcelDetails(id),
  );
  return { parcel: data?.parcel ?? null, events: data?.parcelEvents ?? [] };
}

export type StatusAction = 'delivered' | 'partial' | 'return';

/**
 * Déclare l'issue d'une course.
 *  - livré : l'encaissement attendu est celui du colis ;
 *  - partiel : le montant réellement encaissé est obligatoire ;
 *  - retour : le colis repart vers le transporteur.
 * Les trois passent par `parcel-status-update` (`status_action` du backend).
 */
export async function reportOutcome(
  parcelId: number,
  action: StatusAction,
  options: { cashCollection?: number; note?: string } = {},
): Promise<void> {
  const statusAction = {
    delivered: BackendParcelStatus.DELIVERED,
    partial: BackendParcelStatus.PARTIAL_DELIVERED,
    return: BackendParcelStatus.RETURN_TO_COURIER,
  }[action];

  await api.post(endpoints.parcelStatusUpdate, {
    parcel_id: parcelId,
    status_action: statusAction,
    cash_collection: options.cashCollection,
    note: options.note?.trim() || undefined,
  });
}

/**
 * Déclare une course livrée avec, si fournie, la photo du colis remis.
 * `deliveryman/parcel/delivered/{id}` accepte `note` et le fichier `image`
 * (multipart) ; le backend l'enregistre sur l'événement de livraison.
 */
export async function reportDelivered(
  parcelId: number,
  options: { note?: string; photoUri?: string } = {},
): Promise<void> {
  const form = new FormData();
  if (options.note?.trim()) form.append('note', options.note.trim());
  if (options.photoUri) {
    // React Native accepte un descripteur { uri, name, type } comme partie de fichier.
    form.append('image', {
      uri: options.photoUri,
      name: `livraison-${parcelId}.jpg`,
      type: 'image/jpeg',
    } as unknown as Blob);
  }
  await api.post(endpoints.parcelDelivered(parcelId), form);
}

/** Position courante, écrite sur toutes les courses en cours du livreur. */
export async function updateLocation(lat: number, long: number): Promise<void> {
  await api.post(endpoints.locationUpdate, { lat, long });
}

export async function fetchIncomeExpense(): Promise<IncomeExpenseData> {
  const data = await api.get<Partial<IncomeExpenseData>>(endpoints.incomeExpense);
  return { income: data?.income ?? [], expense: data?.expense ?? [], deliveryInfo: data?.deliveryInfo };
}

export async function fetchParcelPaymentLogs(): Promise<ParcelPaymentLog[]> {
  const data = await api.get<{ parcel_payment_logs: ParcelPaymentLog[] }>(endpoints.parcelPaymentLogs);
  return data?.parcel_payment_logs ?? [];
}
