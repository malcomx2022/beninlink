/**
 * Colis et boutiques.
 *
 * ✅ La création est de nouveau possible depuis que **S2 est corrigé** côté web/
 * (`App\Services\Parcel\ChargeCalculator`, 2026-08-18) : le serveur recalcule
 * frais, TVA, total et net à reverser à partir du barème et des taux du
 * marchand. L'app n'envoie donc **que des choix** — boutique, catégorie, type de
 * livraison, poids, destinataire, montant à encaisser — et **aucun montant
 * calculé**. Vérifié : une création sans `chargeDetails` aboutit, et un
 * `chargeDetails` falsifié est ignoré.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import type {
  Parcel,
  ParcelEvent,
  ParcelFormData,
  ParcelQuote,
  ParcelStatusOption,
} from './types';

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

/** Référentiels du formulaire de création (boutiques, catégories, emballages…). */
export function fetchParcelFormData(): Promise<ParcelFormData> {
  return api.get<ParcelFormData>(endpoints.parcelCreate);
}

export type NewParcel = {
  shop_id: number;
  category_id: number;
  delivery_type_id: number;
  customer_name: string;
  customer_phone: string;
  customer_address: string;
  /** Montant à encaisser auprès du destinataire (COD). */
  cash_collection: number;
  selling_price?: number;
  weight?: string | number;
  invoice_no?: string;
  note?: string;
  packaging_id?: number;
  /** Le backend attend littéralement la chaîne « on ». */
  fragileLiquid?: 'on';
};

/**
 * Crée un colis.
 *
 * **Aucun montant calculé n'est transmis** : frais, TVA, total et net à reverser
 * sont établis par le serveur. Ne jamais rajouter de `chargeDetails` ici — ce
 * serait revenir à la faille S2.
 */
export function createParcel(parcel: NewParcel): Promise<unknown> {
  return api.post(endpoints.parcelStore, parcel);
}


export type QuoteInput = {
  category_id: number;
  delivery_type_id: number;
  cash_collection: number;
  weight?: string | number;
  packaging_id?: number;
};

/**
 * Devis : demande au serveur ce que coûtera le colis, sans rien créer.
 *
 * C'est la réponse à la question « combien ? » posée avant la création. Le
 * calcul reste **entièrement** côté serveur — on affiche le devis, on ne le
 * refait pas, sinon on rouvre S2 et les deux barèmes finissent par diverger.
 *
 * `signal` sert à abandonner un devis devenu obsolète pendant la saisie.
 */
export function fetchQuote(input: QuoteInput, signal?: AbortSignal): Promise<ParcelQuote> {
  return api.post<ParcelQuote>(endpoints.parcelQuote, input, { signal });
}
