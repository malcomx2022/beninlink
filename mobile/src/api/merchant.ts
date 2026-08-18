/** Tableau de bord, profil, relevés de règlement et barème du marchand connecté. */
import { api } from './client';
import { endpoints } from './endpoints';
import type {
  AuthUser,
  BalanceDetails,
  CodCharge,
  DashboardData,
  DeliveryRate,
  Invoice,
  InvoiceDetails,
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

/** Nombre de factures par page — fixé côté serveur par `paginate(10)`. */
export const INVOICES_PER_PAGE = 10;

/**
 * Relevés de règlement émis (factures marchand), page par page.
 *
 * `InvoiceResource::collection()` est renvoyée sur un paginateur : la réponse est
 * `{data: [...], links, meta}` et le client ne garde que `data`. Les compteurs de
 * `meta` sont donc hors de portée — d'où la règle simple côté écran : une page
 * incomplète est la dernière.
 */
export async function fetchInvoices(page = 1): Promise<Invoice[]> {
  const data = await api.get<Invoice[]>(endpoints.invoiceList, { query: { page } });
  return Array.isArray(data) ? data : [];
}

/** Ventilation d'une facture : encaissé, frais, COD, retours, net à reverser. */
export function fetchInvoiceDetails(id: number): Promise<InvoiceDetails> {
  return api.get<InvoiceDetails>(endpoints.invoiceDetails(id));
}

/** Barème de livraison du marchand : une ligne par catégorie et par poids. */
export async function fetchDeliveryRates(): Promise<DeliveryRate[]> {
  const data = await api.get<{ deliveryCharges: DeliveryRate[] }>(endpoints.deliveryCharges);
  return data?.deliveryCharges ?? [];
}

/** Taux d'encaissement (COD) par zone, en pourcentage. */
export async function fetchCodCharges(): Promise<CodCharge[]> {
  const data = await api.get<{ codCharges: CodCharge[] }>(endpoints.codCharges);
  return data?.codCharges ?? [];
}
