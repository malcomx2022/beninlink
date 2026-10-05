/**
 * Argent du marchand : historique du porte-monnaie prépayé, comptes de
 * règlement et demandes de retrait (payout).
 *
 * Deux notions distinctes, comme le rappelle la revue fonctionnelle §3.3 :
 *   - le **porte-monnaie prépayé** (`merchants.wallet_balance`), rechargé par
 *     FedaPay et débité des frais — historique via `wallet/history` ;
 *   - le **net à reverser** (`merchants.current_balance`), encaissements COD
 *     dus au marchand — c'est lui qu'un retrait vient prélever.
 * Le retrait n'est pas un paiement instantané : c'est une **demande** que
 * l'équipe BeninLink approuve puis règle sur le compte désigné.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { toPaged, type Paged } from './pagination';
import type { PaymentAccount, PaymentRequest, WalletEntry } from './types';

/** Mouvements servis par page ; fixé côté serveur par `paginate(10)`. Repli si `page` manque (S78). */
export const WALLET_HISTORY_PER_PAGE = 10;

/**
 * Mouvements du porte-monnaie, du plus récent au plus ancien.
 * Paginé par 10 côté serveur ; depuis S78 la réponse dit où finit la liste.
 */
export async function fetchWalletHistory(page = 1): Promise<Paged<WalletEntry>> {
  const { data, page: pageInfo } = await api.getPaged<{ entries: WalletEntry[] }>(
    endpoints.walletHistory,
    { query: { page } },
  );
  return toPaged(data?.entries ?? [], pageInfo, WALLET_HISTORY_PER_PAGE);
}

export async function fetchPaymentAccounts(): Promise<PaymentAccount[]> {
  const data = await api.get<{ accounts: PaymentAccount[] }>(endpoints.paymentAccounts);
  return data?.accounts ?? [];
}

/** Opérateurs Mobile Money du Bénin (décision actée). Valeurs stockées telles quelles. */
export const MOBILE_MONEY_OPERATORS = ['MTN MoMo', 'Moov Money'] as const;
export type MobileMoneyOperator = (typeof MOBILE_MONEY_OPERATORS)[number];

export type MobileMoneyAccountPayload = {
  holder_name: string;
  operator: MobileMoneyOperator;
  /** Numéro avec indicatif, 11 à 14 chiffres (règle backend). */
  mobile_no: string;
};

/**
 * Ajoute un compte Mobile Money comme destination de retrait.
 * Le backend distingue les comptes par `payment_method` ; les champs mobiles
 * portent le préfixe `mobile_` dans la requête (`PaymentAccount\StoreRequest`).
 */
export function createMobileMoneyAccount(payload: MobileMoneyAccountPayload): Promise<void> {
  return api.post(endpoints.paymentAccountStore, {
    payment_method: 'mobile',
    mobile_holder_name: payload.holder_name,
    mobile_company: payload.operator,
    mobile_no: payload.mobile_no,
    // Libellé français de `merchant.personal` : le panneau web stocke le
    // libellé traduit, pas la clé.
    account_type: 'Personnel',
  });
}

export async function fetchPaymentRequests(): Promise<PaymentRequest[]> {
  const data = await api.get<{ payments: PaymentRequest[] }>(endpoints.paymentRequestIndex);
  return data?.payments ?? [];
}

export type PaymentRequestPayload = {
  /** Entier en FCFA, au plus le net à reverser (`current_balance`). */
  amount: number;
  /** Identifiant d'un compte de règlement **du marchand** (vérifié côté serveur). */
  merchant_account: number;
  description?: string;
};

export function createPaymentRequest(payload: PaymentRequestPayload): Promise<void> {
  return api.post(endpoints.paymentRequestStore, {
    amount: Math.round(payload.amount),
    merchant_account: payload.merchant_account,
    ...(payload.description ? { description: payload.description } : {}),
  });
}
