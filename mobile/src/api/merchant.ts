/** Tableau de bord, profil, relevés de règlement et barème du marchand connecté. */
import { api } from './client';
import { endpoints } from './endpoints';
import { toPaged, type Paged } from './pagination';
import type {
  AuthUser,
  BalanceDetails,
  CodCharge,
  DashboardData,
  DeliveryDelay,
  DeliveryGrid,
  DeliveryRate,
  DeliveryZone,
  Invoice,
  InvoiceDetails,
  ProfileUpdatePayload,
} from './types';

export function fetchDashboard(): Promise<DashboardData> {
  return api.get<DashboardData>(endpoints.dashboard);
}

/**
 * Relevé de règlement en cours : COD encaissé − frais − TVA = net à reverser.
 * Cet endpoint renvoie l'objet nu ; le client le laisse passer tel quel puisqu'il
 * ne comporte pas de clé `data`.
 */
export function fetchBalanceDetails(): Promise<BalanceDetails> {
  return api.get<BalanceDetails>(endpoints.balanceDetails);
}

export function fetchProfile(): Promise<AuthUser> {
  return api.get<AuthUser>(endpoints.profile);
}

/** Nombre de factures par page — fixé côté serveur par `paginate(10)`. Repli si `page` manque (S78). */
export const INVOICES_PER_PAGE = 10;

/**
 * Relevés de règlement émis (factures marchand), page par page.
 *
 * Jusqu'à S78 le serveur renvoyait le paginateur nu (`{data, links, meta}`) et
 * le client ne gardait que `data`. Il renvoie désormais l'enveloppe du projet :
 * `data` est toujours le tableau des relevés, et `page` à la racine dit où finit
 * la liste. Un serveur d'avant S78 continue de marcher : sans `page`, une page
 * incomplète est la dernière.
 */
export async function fetchInvoices(page = 1): Promise<Paged<Invoice>> {
  const { data, page: pageInfo } = await api.getPaged<Invoice[]>(endpoints.invoiceList, {
    query: { page },
  });
  return toPaged(Array.isArray(data) ? data : [], pageInfo, INVOICES_PER_PAGE);
}

/** Ventilation d'une facture : encaissé, frais, COD, retours, net à reverser. */
export function fetchInvoiceDetails(id: number): Promise<InvoiceDetails> {
  return api.get<InvoiceDetails>(endpoints.invoiceDetails(id));
}

/**
 * Lien de téléchargement du relevé en PDF, signé et valable 15 minutes.
 * L'app l'ouvre dans le navigateur : elle ne peut pas y joindre son jeton.
 */
export async function fetchInvoicePdfLink(id: number): Promise<string> {
  const data = await api.get<{ url: string; expires_at: string }>(endpoints.invoicePdfLink(id));
  return data.url;
}

/**
 * Le barème du marchand, dans **les deux formes** (**D4**).
 *
 * Le serveur sert les quatre colonnes héritées et, à côté, les zones. `zones`
 * arrive vide tant que le transporteur n'en a pas configuré — et un serveur
 * antérieur à la refonte n'envoie tout simplement pas la clé. Les deux cas
 * donnent ici un tableau vide, et l'écran retombe sur `rates` : c'est ce qui
 * permet à cette version de tourner sur les deux générations de serveur.
 */
export async function fetchDeliveryGrid(): Promise<DeliveryGrid> {
  const data = await api.get<{
    deliveryCharges?: DeliveryRate[];
    zones?: DeliveryZone[];
    delays?: DeliveryDelay[];
  }>(endpoints.deliveryCharges);

  return {
    rates: data?.deliveryCharges ?? [],
    zones: data?.zones ?? [],
    delays: data?.delays ?? [],
  };
}

/** Taux d'encaissement (COD) par zone, en pourcentage. */
export async function fetchCodCharges(): Promise<CodCharge[]> {
  const data = await api.get<{ codCharges: CodCharge[] }>(endpoints.codCharges);
  return data?.codCharges ?? [];
}

/** Met à jour l'identité du compte. Voir `ProfileUpdatePayload` : les cinq champs sont requis. */
export function updateProfile(payload: ProfileUpdatePayload): Promise<void> {
  return api.post(endpoints.profileUpdate, payload);
}

/**
 * Change le mot de passe. Le backend vérifie l'ancien et répond 422 avec un
 * message explicite s'il ne correspond pas ; minimum 8 caractères (S134).
 */
export function updatePassword(oldPassword: string, newPassword: string): Promise<void> {
  return api.put(endpoints.updatePassword, {
    old_password: oldPassword,
    new_password: newPassword,
    confirm_password: newPassword,
  });
}
