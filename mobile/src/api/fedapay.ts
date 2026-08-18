/**
 * Recharge du portefeuille par Mobile Money (FedaPay).
 *
 * ⚠️ L'app **n'accède jamais aux clés FedaPay** : elle demande au backend
 * d'initier la transaction, ouvre l'URL hébergée qu'il renvoie, et rien de plus.
 * Le client choisit MTN ou Moov et valide par USSD sur la page de FedaPay.
 *
 * ⚠️ **Le retour de la page ne prouve rien.** Le solde n'est à jour qu'après le
 * webhook signé reçu par le serveur (`.claude/rules/mobile.md`). D'où
 * `fetchRechargeStatus()` : on interroge le serveur, on ne déduit rien de la
 * fermeture du navigateur.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import type { Amount } from './types';

export type RechargeInitiation = {
  reference: string;
  payment_url: string;
};

export type RechargeStatus = {
  reference: string;
  /** pending | approved | declined | canceled — miroir des événements FedaPay. */
  status: string;
  amount: Amount;
  /** Solde après traitement : c'est lui qui fait foi, pas le statut. */
  wallet_balance: Amount;
};

/**
 * Demande une recharge. Le montant est un **entier** en FCFA.
 * Renvoie l'URL de paiement à ouvrir.
 */
export function initiateRecharge(amount: number): Promise<RechargeInitiation> {
  return api.post<RechargeInitiation>(endpoints.fedapayInitiate, {
    amount: Math.round(amount),
  });
}

export function fetchRechargeStatus(reference: string): Promise<RechargeStatus> {
  return api.get<RechargeStatus>(endpoints.fedapayStatus(reference));
}
