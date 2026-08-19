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
import type { CustomsAlert, CustomsReference } from './types';

/** Alertes servies par page ; fixé côté serveur par `paginate(20)`. */
export const CUSTOMS_ALERTS_PER_PAGE = 20;

/** Pays et catégories couverts — sert à peupler les listes de création. */
export function fetchCustomsReference(): Promise<CustomsReference> {
  return api.get<CustomsReference>(endpoints.customsReference);
}

/**
 * Alertes du marchand. `status` : 1 en cours, 2 traitées, absent = toutes.
 *
 * La collection est imbriquée dans l'enveloppe côté serveur : elle arrive donc
 * en tableau nu, sans les compteurs du paginateur.
 */
export async function fetchCustomsAlerts(status?: number, page = 1): Promise<CustomsAlert[]> {
  const data = await api.get<{ alerts: CustomsAlert[] }>(endpoints.customsAlerts, {
    query: { status, page },
  });
  return data?.alerts ?? [];
}

/** Le marchand déclare avoir réuni le document. */
export async function resolveCustomsAlert(id: number): Promise<CustomsAlert | null> {
  const data = await api.put<{ alert: CustomsAlert }>(endpoints.customsResolve(id));
  return data?.alert ?? null;
}
