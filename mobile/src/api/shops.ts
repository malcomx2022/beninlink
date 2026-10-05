/**
 * Boutiques du marchand (`shops/*`).
 *
 * Le backend exige `status` (1 = active) et un `contact_no` numérique de 11 à
 * 14 chiffres — règle héritée du socle, calibrée sur l'indicatif : on saisit
 * donc « 22997000000 », pas « 97 00 00 00 ».
 *
 * Depuis le 2026-09-04, `edit`, `update` et `delete` ne rendent que les
 * boutiques du marchand connecté (404 sinon) : l'app n'a rien à vérifier.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { fetchAllPages, toPaged, type Paged } from './pagination';
import type { Shop } from './types';

export type ShopPayload = {
  name: string;
  contact_no: string;
  address: string;
};

/** 1 = active (App\Enums\Status::ACTIVE). L'app ne gère pas la désactivation. */
const ACTIVE = 1;

/** Boutiques servies par page — `paginate(10)` d'un dépôt partagé avec le back-office. Repli si `page` manque (S78). */
export const SHOPS_PER_PAGE = 10;

/** Une page de boutiques ; `page` à la racine de l'enveloppe depuis S78. */
export async function fetchShopsPage(page = 1): Promise<Paged<Shop>> {
  const { data, page: pageInfo } = await api.getPaged<{ shops: Shop[] }>(endpoints.shopsIndex, {
    query: { page },
  });
  return toPaged(data?.shops ?? [], pageInfo, SHOPS_PER_PAGE);
}

/**
 * Toutes les boutiques du marchand.
 *
 * ⚠️ Avant S78, l'app lisait la première page sans le savoir : le serveur
 * servait dix boutiques et aucune réponse ne disait qu'il en restait. La liste
 * et le choix de boutique à la création d'un colis parcourent désormais toutes
 * les pages.
 */
export function fetchShops(): Promise<Shop[]> {
  return fetchAllPages(fetchShopsPage);
}

export async function fetchShop(id: number): Promise<Shop> {
  const data = await api.get<{ shop: Shop }>(endpoints.shopsEdit(id));
  return data.shop;
}

export function createShop(payload: ShopPayload): Promise<void> {
  return api.post(endpoints.shopsStore, { ...payload, status: ACTIVE });
}

export function updateShop(id: number, payload: ShopPayload): Promise<void> {
  return api.put(endpoints.shopsUpdate(id), { ...payload, status: ACTIVE });
}

export function deleteShop(id: number): Promise<void> {
  return api.delete(endpoints.shopsDelete(id));
}
