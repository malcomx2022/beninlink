/**
 * Parcours d'une liste paginée (S78, T8).
 *
 * Le serveur dit désormais où finit la liste : le bloc `page` de l'enveloppe
 * (`current`, `per_page`, `last`, `total`). Les constantes `*_PER_PAGE` restent
 * pour deux raisons : un serveur d'avant S78 ne renvoie pas `page`, et
 * `MerchantAppCustomsContractTest` (côté `web/`) compare ces constantes aux
 * `paginate()` du serveur — c'est le filet qui attrape une taille qui bouge
 * d'un seul côté.
 *
 * Règle : `page` présent → `current < last` ; sinon « une page incomplète est la
 * dernière », la seule déduction possible sans compteur.
 */
import type { ApiPage } from './client';

/** Une page d'une liste, et s'il en reste. */
export type Paged<T> = {
  items: T[];
  hasMore: boolean;
};

export function hasNextPage(page: ApiPage | null, received: number, perPage: number): boolean {
  if (page) return page.current < page.last;
  return received >= perPage;
}

export function toPaged<T>(items: T[], page: ApiPage | null, perPage: number): Paged<T> {
  return { items, hasMore: hasNextPage(page, items.length, perPage) };
}

/**
 * Lit TOUTES les pages d'une liste que l'écran affiche entière (boutiques).
 *
 * Le dépôt de ces routes pagine par 10 pour les tables du back-office, et
 * l'API servait la première page sans le dire : un marchand à onze boutiques
 * en voyait dix (trouvé par `ApiPaginationContractTest`, S78). Le plafond
 * protège d'une boucle sur un serveur qui dirait toujours « il en reste ».
 */
export async function fetchAllPages<T>(
  fetchPage: (page: number) => Promise<Paged<T>>,
  maxPages = 50,
): Promise<T[]> {
  const items: T[] = [];
  for (let page = 1; page <= maxPages; page++) {
    const result = await fetchPage(page);
    items.push(...result.items);
    if (!result.hasMore || result.items.length === 0) break;
  }
  return items;
}
