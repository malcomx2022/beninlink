/**
 * Inventaire des endpoints `/api/v10` consommés par l'app livreur.
 *
 * Tous existent côté web/ (voir `GET /api/v10/openapi.json`, tag « Livreur ») ;
 * depuis S5 ils exigent un jeton portant l'ability `deliveryman`, émis par
 * `deliveryman/login`. Ne jamais inventer d'endpoint : `web/` est le contrat.
 */
export const endpoints = {
  // — Session (routes communes aux deux apps)
  login: 'deliveryman/login',
  refresh: 'refresh',
  signOut: 'sign-out',
  updatePassword: 'update-password',

  // — Espace livreur
  /** Quatre listes (assignés, reprogrammés, retours, livrés) en ParcelResource. */
  dashboard: 'deliveryman/dashboard',
  profile: 'deliveryman/profile',
  /** Colis confiés, bruts (sans libellés) — la liste des onglets vient du dashboard. */
  parcelIndex: 'deliveryman/parcel/index',
  parcelDetails: (id: number | string) => `deliveryman/parcel/details/${id}`,
  parcelDelivered: (id: number | string) => `deliveryman/parcel/delivered/${id}`,
  parcelPartialDelivered: (id: number | string) => `deliveryman/parcel/partial-delivered/${id}`,
  /** Retour au coursier (et, en double, livré / partiel) : `parcel_id` + `status_action`. */
  parcelStatusUpdate: 'deliveryman/parcel-status-update',
  parcelStatuses: 'deliveryman/parcel-status',
  /** Position du livreur, écrite sur ses courses en cours (S4, S7). */
  locationUpdate: 'deliveryman/parcel-location-update',
  incomeExpense: 'deliveryman/income-expense',
  paymentLogs: 'deliveryman/payment-logs',
  parcelPaymentLogs: 'deliveryman/parcel-payment-logs',
} as const;
