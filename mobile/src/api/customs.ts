/**
 * Alertes douanières UEMOA / CEDEAO (chantier 5).
 *
 * ⚠️ Aucune règle douanière ne vit dans l'app. Savoir si un colis passe est une
 * question posée au serveur : la réponse arrive dans `parcel/quote`, que l'écran
 * de création appelle déjà. Ici on ne fait que lire le référentiel et
 * l'historique des alertes.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { toPaged, type Paged } from './pagination';
import type { CustomsAlert, CustomsReference } from './types';

/** Alertes servies par page ; fixé côté serveur par `paginate(20)`. Repli si `page` manque (S78). */
export const CUSTOMS_ALERTS_PER_PAGE = 20;

/** Pays et catégories couverts — sert à peupler les listes de création. */
export function fetchCustomsReference(): Promise<CustomsReference> {
  return api.get<CustomsReference>(endpoints.customsReference);
}

/**
 * Alertes du marchand. `status` : 1 en cours, 2 traitées, absent = toutes.
 *
 * Depuis S78 la réponse porte `page` à la racine : `hasMore` vient de
 * `page.current < page.last`, et retombe sur la constante si le serveur ne le
 * dit pas (`src/api/pagination.ts`).
 */
export async function fetchCustomsAlerts(status?: number, page = 1): Promise<Paged<CustomsAlert>> {
  const { data, page: pageInfo } = await api.getPaged<{ alerts: CustomsAlert[] }>(
    endpoints.customsAlerts,
    { query: { status, page } },
  );
  return toPaged(data?.alerts ?? [], pageInfo, CUSTOMS_ALERTS_PER_PAGE);
}

/** Le marchand déclare avoir réuni le document. */
export async function resolveCustomsAlert(id: number): Promise<CustomsAlert | null> {
  const data = await api.put<{ alert: CustomsAlert }>(endpoints.customsResolve(id));
  return data?.alert ?? null;
}
