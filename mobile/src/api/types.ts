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
 * Colis tel que le renvoie `GET parcel/index` et `parcel/details/{id}`.
 *
 * `statusName` et `deliveryType` arrivent **déjà traduits** par le backend : on
 * les affiche tels quels plutôt que de maintenir un second jeu de libellés.
 * `status` (entier) sert au regroupement en onglets (voir domain/parcelStatus).
 */
export type Parcel = {
  id: number;
  tracking_id: string;
  customer_name: string;
  customer_phone: string | null;
  customer_address: string | null;
  invoice_no: string | null;
  /** Chaîne déjà formatée, ex. « 1 KG ». */
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
  /** Dates déjà mises en forme par le backend (« 17 Aug 2026, 09:29 PM »). */
  created_at: string | null;
  parcel_date: string | null;
  parcel_time: string | null;
};

/**
 * Étape du suivi, telle que la sert `ParcelLogsResource` côté web/ (`parcel/logs`,
 * `parcel/details`) : libellé déjà traduit, date déjà formatée. Vide sur un
 * colis neuf : les événements naissent des transitions.
 */
export type ParcelEvent = {
  id: number;
  /** Code de statut, sérialisé en chaîne par la ressource. */
  parcel_status: string;
  parcel_status_name: string;
  description?: string | null;
  hub_name?: string;
  delivery_man?: string;
  delivery_phone?: string;
  date?: string;
  time_date?: string;
  /** Preuves posées par le livreur sur « Livré » (URL absolues), sinon null. */
  delivered_image?: string | null;
  signature_image?: string | null;
};

/** `GET parcel/all/status` — liste **nue** des 10 statuts marchand, traduits. */
export type ParcelStatusOption = {
  id: number;
  status: string;
};

/**
 * `GET parcel/create` — référentiels du formulaire.
 *
 * ⚠️ `deliveryTypes` n'est **pas** une liste d'identifiants : ce sont des
 * interrupteurs de configuration (`{key, value}` où `value = "1"` signifie
 * activé). Les identifiants à poster viennent de `App\Enums\DeliveryType`,
 * repris dans `domain/deliveryType.ts`.
 */
export type ParcelFormData = {
  shops: Shop[];
  /** Objet indexé par identifiant, pas un tableau. */
  deliveryCategories: Record<string, { id: number; title: string }>;
  deliveryTypes: { key: string; value: string }[];
  packagings: { id: number; name: string; price: Amount }[];
  codCharges: { name: string; charge: string }[];
  fragileLiquid: Amount;
};

export type Shop = {
  id: number;
  name: string;
  contact_no: string | null;
  address: string | null;
  default_shop: string | number;
  statusName: string | null;
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

/**
 * `GET /invoice-list/index` — une ligne de la liste (`InvoiceResource`).
 *
 * ⚠️ Le backend pagine (10 par page) et renvoie l'enveloppe d'un paginateur ;
 * le client ne conserve que `data`. Les 10 dernières factures suffisent au MVP.
 */
export type Invoice = {
  id: number;
  invoice_id: string;
  /** Libellé déjà traduit (Payé / Impayé / En cours). */
  status: string | null;
  /** Net à reverser de la facture, retours déduits. */
  amount: Amount;
  /** Déjà mise en forme par le backend (« 14 Jul 2026 »). */
  invoice_date: string | null;
};

/**
 * `GET /invoice-details/{id}` — la ventilation d'une facture.
 *
 * ⚠️ `total_deliverd_amount` : la faute de frappe vient du backend, on garde la
 * clé telle qu'elle arrive.
 * ⚠️ La réponse porte aussi une clé `parcels`, **toujours nulle** : elle lit un
 * accesseur `InvoiceParcelList` qui n'existe pas sur le modèle. Ne pas l'afficher
 * tant que `web/` ne l'a pas ajouté.
 */
export type InvoiceDetails = Invoice & {
  total_deliverd_amount: Amount;
  delivery_charge: Amount;
  cod_amount: Amount;
  total_return_fee: Amount;
  /** Net à reverser = encaissé − frais − COD − retours. */
  payable_amount: Amount;
  merchant_name: string | null;
  merchant_phone: string | null;
  merchant_address: string | null;
  total_parcels: number;
};

/**
 * `GET /settings/delivery-charges` — une ligne du barème (poids × zone).
 *
 * ⚠️ Tous les montants arrivent en **chaînes** (`(string)` explicite dans
 * `DeliveryChargeResource`) : passer par `toAmount()` comme partout ailleurs.
 * ⚠️ `weight` est une valeur de tranche **comparée à l'identique** par le
 * calculateur serveur, pas un plafond : afficher « 1 kg », jamais « jusqu'à 1 kg ».
 */
export type DeliveryRate = {
  id: number;
  category: string | null;
  weight: string | null;
  same_day: string;
  next_day: string;
  sub_city: string;
  outside_city: string;
  status: string;
  statusName: string | null;
};

/** `GET /settings/cod-charges` — taux d'encaissement en **pourcentage**, par zone. */
export type CodCharge = {
  /** Libellé déjà traduit par le backend (`__('merchant.inside_city')`…). */
  name: string;
  charge: string;
  /**
   * Code de la zone à laquelle ce taux se rattache (**D4**).
   *
   * Optionnel : un serveur antérieur à la refonte ne l'envoie pas. `null` quand
   * la clé n'appartient à aucune zone — la zone d'export, dont le taux n'est
   * pas encore fixé.
   */
  zone_code?: string | null;
};

/**
 * Le barème par **zones** (**D4**), servi à côté des quatre colonnes.
 *
 * Le serveur sert les deux formes pendant la transition. `zones` arrive **vide**
 * tant que le transporteur n'a pas configuré les siennes : l'app reste alors sur
 * `DeliveryRate`, exactement comme avant. C'est ce qui permet à cette version de
 * tourner sur un serveur qui n'a pas encore basculé, et à l'ancienne de tourner
 * sur un serveur qui a basculé.
 *
 * ⚠️ Montants en **chaînes**, FCFA entiers : passer par `toAmount()`.
 */
export type ZoneRate = {
  category_id: string;
  /** Tranche comparée à l'identique par le serveur, pas un plafond. */
  weight: string;
  amount: string;
};

/** Un pays d'une zone d'export et son **forfait**, indépendant du poids. */
export type ZoneCountry = {
  /** ISO 3166-1 alpha-2. */
  code: string;
  name: string;
  flat_amount: string;
};

export type DeliveryZone = {
  id: number;
  /** Stable : le taux COD et la conversion s'y rattachent. */
  code: string;
  name: string;
  position: number;
  /** Zone facturée au pays, au forfait, sans regarder le poids. */
  export: boolean;
  /** Entrée de `CodCharge` applicable ; `null` sur la zone d'export. */
  cod_key: string | null;
  rates: ZoneRate[];
  countries: ZoneCountry[];
};

/**
 * Un délai et son supplément — **global**, jamais par zone.
 *
 * Il s'ajoute au montant de la zone. L'afficher dans chaque colonne redonnerait
 * le mélange délai × périmètre que la refonte défait.
 */
export type DeliveryDelay = {
  id: number;
  code: string;
  name: string;
  surcharge: string;
  position: number;
};

/** Ce que rend `GET /settings/delivery-charges` : les deux formes à la fois. */
export type DeliveryGrid = {
  rates: DeliveryRate[];
  zones: DeliveryZone[];
  delays: DeliveryDelay[];
};

/**
 * `POST parcel/quote` — les montants d'un colis **avant** sa création.
 *
 * Même `ChargeCalculator` que la création : ce que le devis annonce est ce que
 * le serveur enregistrera. L'app affiche ces valeurs, elle n'en dérive aucune.
 *
 * ⚠️ `vat` et `cod_charge` sont des **taux en pourcentage** (`formatRate`), pas
 * des montants. `total_delivery_amount` est le sous-total **hors TVA** ;
 * `total_payable_charges` est le total que paie le marchand, TVA comprise.
 */
export type ParcelQuote = {
  delivery_charge: Amount;
  cod_charge: Amount;
  cod_amount: Amount;
  vat: Amount;
  vat_amount: Amount;
  packaging_amount: Amount;
  liquid_fragile_amount: Amount;
  total_delivery_amount: Amount;
  total_payable_charges: Amount;
  /** Net à reverser au marchand après déduction des frais. */
  current_payable: Amount;
  /**
   * Règle douanière applicable, ou `null` pour un colis domestique.
   * Le devis répond aussi à « ce colis passe-t-il ? » : l'écran de création
   * l'appelle déjà à chaque changement, inutile d'interroger une seconde route.
   */
  customs: CustomsRule | null;
};

/** Règle douanière telle que la rend `parcel/quote`. */
export type CustomsRule = {
  /** 1 info · 2 avertissement · 3 bloquant (App\Enums\CustomsLevel). */
  level: number;
  /** Libellé déjà traduit par le backend. */
  level_name: string;
  /** `true` : la création sera refusée tant que le document manque. */
  blocking: boolean;
  required_document: string | null;
  message: string;
};

/** `GET customs/reference` — de quoi peupler les listes de l'écran de création. */
export type CustomsReference = {
  countries: { code: string; name: string }[];
  categories: { slug: string; name: string }[];
};

/**
 * `GET customs/alerts` — une alerte émise.
 *
 * ⚠️ Le backend pagine (20) mais la collection est imbriquée dans l'enveloppe :
 * elle arrive donc en tableau nu, sans compteurs. Même règle que les factures —
 * une page incomplète est la dernière.
 */
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

/** Charge utile de `POST /profile/update`.
 *
 * ⚠️ Le backend ne valide que `name` et `address`, mais son repository
 * **réécrit** aussi `mobile`, `email` et `business_name` avec ce qu'il reçoit :
 * omettre un champ l'effacerait en base. On envoie donc toujours les cinq.
 */
export type ProfileUpdatePayload = {
  name: string;
  email: string;
  mobile: string;
  business_name: string;
  address: string;
};

/**
 * `GET /payment-accounts/index` — un compte de règlement du marchand
 * (`PaymentAccountResource`). Sert de destination aux retraits.
 *
 * `payment_method` vaut `bank`, `mobile` ou `cash`. Pour le Mobile Money
 * béninois, `mobile_company` porte l'opérateur (« MTN MoMo », « Moov Money »).
 */
export type PaymentAccount = {
  id: number;
  payment_method: 'bank' | 'mobile' | 'cash' | string;
  paymentMethodName: string | null;
  bank_name: string | null;
  holder_name: string | null;
  account_no: string | null;
  branch_name: string | null;
  routing_no: string | null;
  mobile_company: string | null;
  mobile_no: string | null;
  account_type: string | null;
  status: number;
  statusName: string | null;
};

/**
 * `GET payment-request/index` — une demande de retrait (`PaymentResource`).
 * ⚠️ `amount` arrive en **chaîne** (`(string)` explicite côté backend).
 */
export type PaymentRequest = {
  id: number;
  transaction_id: string;
  description: string | null;
  amount: Amount;
  paymentMethodName: string | null;
  mobile_company: string | null;
  mobile_no: string | null;
  bank_name: string | null;
  account_no: string | null;
  holder_name: string | null;
  /** App\Enums\ApprovalStatus : 1 rejeté · 2 approuvé · 3 en attente · 4 traité. */
  status: number;
  statusName: string | null;
  request_date: string | null;
};

/**
 * `GET wallet/history` — un mouvement du porte-monnaie prépayé (`WalletResource`).
 * Le montant est un entier ; les libellés arrivent traduits.
 */
export type WalletEntry = {
  id: number;
  transaction_id: string;
  source: string | null;
  amount: Amount;
  /** 1 crédit · 2 débit (App\Enums\Wallet\WalletType). */
  type: number;
  typeName: string;
  payment_method: number;
  paymentMethodName: string;
  /** 1 en attente · 2 approuvé · 3 rejeté (App\Enums\Wallet\WalletStatus). */
  status: number;
  statusName: string;
  created_at: string | null;
};

/**
 * `GET notifications/index` — une entrée du fil (`NotificationResource`).
 *
 * `kind` choisit l'icône ; `title` et `body` arrivent rédigés par le serveur.
 * Les identifiants de navigation sont présents selon le `kind`.
 */
export type NotificationKind =
  | 'parcel_status'
  | 'wallet_credit'
  | 'invoice'
  | 'customs'
  | 'message'
  | 'payout';

export type AppNotification = {
  /** UUID Laravel. */
  id: string;
  kind: NotificationKind | string;
  title: string;
  body: string;
  read: boolean;
  created_at: string | null;
  created_at_iso: string | null;
  parcel_id?: number | null;
  tracking_id?: string | null;
  amount?: Amount;
  reference?: string | null;
  status?: number;
  level?: number;
};
