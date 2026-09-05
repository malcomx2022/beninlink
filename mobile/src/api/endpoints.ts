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
  /** Lien signé (15 min) vers le relevé en PDF — chantier 4. */
  invoicePdfLink: (id: number | string) => `invoice-pdf-link/${id}`,
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
  /** Mouvements du porte-monnaie prépayé (recharges, crédits, dépenses). */
  walletHistory: 'wallet/history',

  // — Douane (chantier 5) : alertes UEMOA / CEDEAO
  /** Pays et catégories couverts par les règles en vigueur. */
  customsReference: 'customs/reference',
  customsAlerts: 'customs/alerts',
  customsResolve: (id: number | string) => `customs/alerts/${id}/resolve`,

  // — Profil
  profile: 'profile',
  profileUpdate: 'profile/update',
  updatePassword: 'update-password',

  // — Notifications (fil du marchand : statuts, recharges, relevés, douane, messages, retraits)
  notificationsIndex: 'notifications/index',
  notificationsUnreadCount: 'notifications/unread-count',
  notificationsReadAll: 'notifications/read-all',
  notificationRead: (id: string) => `notifications/${id}/read`,

  // — Notifications poussées : l'appareil s'abonne pour lui-même (D11).
  pushRegister: 'push/register',
  pushForget: 'push/forget',

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
 * ✅ Liste vide depuis le 2026-09-04 : la recharge FedaPay (chantier 3), les
 * alertes douanières (chantier 5), l'historique du wallet (`wallet/history`) et
 * les notifications (`notifications/*`) sont servis par web/.
 * Le solde du wallet, lui, arrive via `/profile` (`merchant.wallet_balance`).
 */
export const MISSING = {} as const;
