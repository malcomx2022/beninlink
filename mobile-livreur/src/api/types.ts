/**
 * Formes de réponse de `/api/v10` côté livreur, relevées sur le backend
 * (`DeliverymanController`, `DeliveryManParcelController`, Resources v10).
 *
 * Les montants arrivent en types mixtes (entier JSON ou chaîne décimale) :
 * tout montant est typé `Amount` et passe par `toAmount()`.
 */

export type Amount = number | string | null;

export type Hub = {
  id: number;
  name: string;
  phone?: string | null;
  address?: string | null;
};

/** Ligne `delivery_man` du compte. */
export type DeliveryMan = {
  id: number;
  status: number;
  delivery_charge: Amount;
  pickup_charge: Amount;
  return_charge: Amount;
  current_balance: Amount;
  opening_balance: Amount;
  delivery_lat?: string | null;
  delivery_long?: string | null;
};

/** `DeliverymanUserResource`. */
export type DeliverymanUser = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  /** Chaîne côté API ; 3 = livreur (App\Enums\UserType). */
  user_type: string | number;
  deliveryman: DeliveryMan | null;
  hub: Hub | null;
  address: string | null;
  salary: Amount;
  status: string | number;
  statusName: string;
  image: string | null;
};

export type SignInResult = {
  token: string;
  user: DeliverymanUser;
};

/** Colis tel que le sert `ParcelResource` (listes du dashboard). */
export type ParcelSummary = {
  id: number;
  tracking_id: string;
  merchant_id: number;
  merchant_name: string | null;
  merchant_user_name?: string | null;
  merchant_mobile: string | null;
  merchant_address: string | null;
  customer_name: string;
  customer_phone: string | null;
  customer_address: string | null;
  invoice_no: string | null;
  weight: string | null;
  total_delivery_amount: Amount;
  cod_amount: Amount;
  vat_amount: Amount;
  current_payable: Amount;
  cash_collection: Amount;
  delivery_type_id: number;
  deliveryType: string | null;
  status: number;
  statusName: string | null;
  pickup_date: string | null;
  delivery_date: string | null;
  created_at: string | null;
  parcel_date: string | null;
  parcel_time: string | null;
};

/** `GET deliveryman/dashboard` — les quatre listes qui font les onglets. */
export type DashboardData = {
  deliveryman_assign: ParcelSummary[];
  deliveryman_re_schedule: ParcelSummary[];
  return_to_courier: ParcelSummary[];
  delivered: ParcelSummary[];
};

/**
 * Colis **brut** (modèle Eloquent + relations) tel que le sert
 * `deliveryman/parcel/details/{id}` : ni libellé de statut ni date formatée.
 */
export type ParcelDetails = {
  id: number;
  tracking_id: string;
  status: number;
  customer_name: string;
  customer_phone: string | null;
  customer_address: string | null;
  customer_lat: string | null;
  customer_long: string | null;
  invoice_no: string | null;
  weight: number | string | null;
  delivery_type_id: number | null;
  cash_collection: Amount;
  delivery_charge: Amount;
  cod_amount: Amount;
  vat_amount: Amount;
  total_delivery_amount: Amount;
  current_payable: Amount;
  packaging_amount: Amount;
  liquid_fragile_amount: Amount;
  note: string | null;
  pickup_date: string | null;
  delivery_date: string | null;
  pickup_address: string | null;
  pickup_phone: string | null;
  merchant: {
    id: number;
    business_name: string;
    address: string | null;
    user?: { name: string; mobile: string | null } | null;
  } | null;
  merchant_shop: {
    id: number;
    name: string;
    contact_no: string | null;
    address: string | null;
    merchant_lat?: string | null;
    merchant_long?: string | null;
  } | null;
  delivery_category: { id: number; title: string } | null;
  packaging: { id: number; name: string; price: Amount } | null;
  created_at: string | null;
};

/** Événement de suivi brut (`parcel_events`). */
export type ParcelEvent = {
  id: number;
  parcel_id: number;
  parcel_status: number | null;
  note: string | null;
  created_at: string | null;
};

/** `GET deliveryman/profile`. */
export type ProfileData = {
  user: DeliverymanUser;
  current_balance: Amount;
  deliveryman_earn: Amount;
  total_cod: Amount;
  delivery_in_progress: number;
  completed_delivered: number;
  canceled_delivered: number;
};

/** `IncomeExpenseResource` (relevés du livreur). */
export type IncomeExpense = {
  id: number;
  parcel_id: number | null;
  note: string | null;
  date: string;
  amount: Amount;
  cash_collection: Amount;
  currency: string;
  /** 1 = revenu, 2 = dépense (App\Enums\StatementType). */
  type: number;
  typeName: string;
  created_at: string | null;
};

export type IncomeExpenseData = {
  income: IncomeExpense[];
  expense: IncomeExpense[];
  deliveryInfo?: Record<string, Amount>;
};

/** `GET deliveryman/parcel-payment-logs` — encaissements COD remis. */
export type ParcelPaymentLog = {
  id: number;
  type: number;
  amount: Amount;
  date: string | null;
  note: string | null;
  created_at: string | null;
};

/** Alerte douanière d'une course (`customs_alerts` sur `deliveryman/parcel/details/{id}`, S95). */
export type CustomsAlert = {
  id: number;
  parcel_id: number | null;
  tracking_id: string | null;
  country_code: string;
  country_name: string;
  goods_category: string;
  category_name: string;
  level: number;
  level_name: string;
  required_document: string | null;
  message: string;
  /** 1 en cours · 2 traitée. */
  status: number;
  status_name: string;
  created_at: string | null;
};
