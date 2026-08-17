/** Tableau de bord et profil du marchand connecté. */
import { api } from './client';
import { endpoints } from './endpoints';
import type { AuthUser, BalanceDetails, DashboardData } from './types';

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
