/**
 * Inventaire des endpoints `/api/v10` **réellement servis** par web/.
 *
 * Établi par la revue fonctionnelle (`docs/REVUE_FONCTIONNELLE_MOBILE.md`) en
 * croisant `web/routes/api.php` et l'app Flutter dépréciée.
 *
 * ⚠️ Règle d'or : **ne jamais inventer d'endpoint**. Ce qui manque est listé dans
 * `MISSING` ci-dessous et attend un chantier côté web/ — ne pas l'appeler « au cas
 * où », le backend renverrait une 404 sans explication.
 */
export const endpoints = {
  // — Authentification (routes publiques : authenticated: false)
  signin: 'signin',
  register: 'register',
  otpVerification: 'otp-verification',
  resendOtp: 'resend-otp',
  passwordEmail: 'password/email',
  passwordReset: 'password/reset',
  signOut: 'sign-out',
  refresh: 'refresh',

  // — Référentiels
  hub: 'hub',
  generalSettings: 'general-settings',
  deliveryCharges: 'settings/delivery-charges',
  codCharges: 'settings/cod-charges',

  // — Tableau de bord
  dashboard: 'dashboard',
  dashboardFilter: 'dashboard/filter',
  analytics: 'analytics',
  availableParcels: 'dashboard/available-parcels',
  /** Net à reverser : COD encaissé − frais − TVA (voir revue §4). */
  balanceDetails: 'dashboard/balance-details',

  // — Colis
  parcelIndex: 'parcel/index',
  parcelFilter: 'parcel/filter',
  parcelCreate: 'parcel/create',
  parcelStore: 'parcel/store',
  /** Devis : montants du serveur AVANT création (voir web/ ParcelController::quote). */
  parcelQuote: 'parcel/quote',
  parcelDetails: (id: number | string) => `parcel/details/${id}`,
  parcelLogs: (id: number | string) => `parcel/logs/${id}`,
  parcelEdit: (id: number | string) => `parcel/edit/${id}`,
  parcelUpdate: (id: number | string) => `parcel/update/${id}`,
  parcelDelete: (id: number | string) => `parcel/delete/${id}`,
  parcelAllStatus: 'parcel/all/status',
  parcelsByStatus: (status: number | string) => `status-wise/parcel/list/${status}`,

  // — Boutiques
  shopsIndex: 'shops/index',
  shopsStore: 'shops/store',
  shopsEdit: (id: number | string) => `shops/edit/${id}`,
  shopsUpdate: (id: number | string) => `shops/update/${id}`,
  shopsDelete: (id: number | string) => `shops/delete/${id}`,

  // — Argent
  invoiceList: 'invoice-list/index',
  invoiceDetails: (id: number | string) => `invoice-details/${id}`,
  statements: 'statements/index',
  accountTransactions: 'account-transaction/index',
  /** Retrait (payout) marchand. */
  paymentRequestIndex: 'payment-request/index',
  paymentRequestStore: 'payment-request/store',
  paymentAccounts: 'payment-accounts/index',
  paymentAccountStore: 'payment-account/store',

  // — FedaPay (chantier 3) : recharge du wallet par Mobile Money
  fedapayInitiate: 'fedapay/initiate',
  fedapayStatus: (reference: string) => `fedapay/status/${reference}`,

  // — Profil
  profile: 'profile',
  profileUpdate: 'profile/update',
  updatePassword: 'update-password',

  // — Divers
  newsOffers: 'news-offer/index',
  supportIndex: 'support/index',
  supportStore: 'support/store',
  fraudIndex: 'fraud/index',
  fraudCheck: 'fraud/check',
} as const;

/**
 * Endpoints **absents du backend** — écrans encore bloqués.
 * Documentés ici pour éviter qu'on les recode à l'aveugle.
 *
 *   Historique du wallet : aucune route ne liste les mouvements de `wallets`.
 *                          `account-transaction/index` couvre les comptes, pas
 *                          le porte-monnaie prépayé.
 *   Alertes douanières   : Module 4, aucune table ni route (chantier 5).
 *   Notifications        : seul `news-offer/index` existe, ce sont des offres.
 *
 * ✅ La recharge FedaPay n'est plus dans cette liste depuis le chantier 3.
 * Le solde du wallet, lui, arrive via `/profile` (`merchant.wallet_balance`).
 */
export const MISSING = {
  walletHistory: null,
  customsAlerts: null,
  notifications: null,
} as const;
