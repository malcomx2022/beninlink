/**
 * Formes de réponse de `/api/v10`, relevées sur le backend en service
 * (et non déduites de la documentation, qui n'existe pas).
 *
 * ⚠️ Les montants arrivent en **types mixtes** : certains champs sont des entiers
 * JSON (`t_balance_proc: 0`), d'autres des chaînes décimales
 * (`wallet_balance: "0.00"`, `t_sale: "0"`). La bascule FCFA du 2026-08-16 n'a
 * touché que les sorties passant par `number_format` ; les attributs de modèle
 * sérialisés tels quels gardent leur forme `decimal(16,2)`.
 * ⇒ Tout montant est typé `Amount` et **doit** passer par `toAmount()`.
 */

/** Montant tel qu'il sort de l'API : entier, chaîne décimale, ou absent. */
export type Amount = number | string | null;

/** Enveloppe d'ApiReturnFormatTrait. Certains endpoints y échappent. */
export type Envelope<T> = {
  success: boolean;
  message: string;
  data: T;
};

export type Hub = {
  id: number;
  name: string;
  phone: string | null;
  address: string | null;
};

export type Merchant = {
  id: number;
  business_name: string;
  merchant_unique_id: string;
  current_balance: Amount;
  opening_balance: Amount;
  /** Porte-monnaie prépayé — non alimentable tant que FedaPay n'est pas branché. */
  wallet_balance: Amount;
  /** Taux de TVA en pourcentage, pas un montant. */
  vat: Amount;
  cod_charges: { inside_city: string; sub_city: string; outside_city: string } | null;
  address: string | null;
  return_charges: Amount;
};

export type AuthUser = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  /** Chaîne côté API ; 2 = marchand (App\Enums\UserType). */
  user_type: string | number;
  address: string | null;
  image: string | null;
  hub: Hub | null;
  merchant: Merchant | null;
};

export type SignInResult = {
  token: string;
  user: AuthUser;
};

/** `GET /dashboard` — compteurs du tableau de bord. */
export type DashboardData = {
  t_parcel: number;
  t_delivered: number;
  t_return: number;
  t_sale: Amount;
  t_delivery_fee: Amount;
  t_balance_proc: Amount;
  t_balance_paid: Amount;
  t_request: number;
  t_shop: number;
  t_fraud: number;
  t_cash_collection: Amount;
  t_vat_amount: Amount;
  merchant: Merchant | null;
};

/**
 * `GET /dashboard/balance-details` — le relevé de règlement.
 * ⚠️ Cet endpoint renvoie l'objet **nu**, sans enveloppe `data`.
 */
export type BalanceDetails = {
  amount_delivered: Amount;
  payable_delivery_charge: Amount;
  sub_total: Amount;
  vat_amount: Amount;
  cod_charge: Amount;
  /** Net à reverser = (sous-total − TVA) − frais COD. */
  available_balance: Amount;
  clearable_parcels: number;
};
