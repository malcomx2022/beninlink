/**
 * Libellés français — locale par défaut et unique du MVP.
 *
 * Toute chaîne visible passe par ici (`.claude/rules/mobile.md`). L'app Flutter
 * dépréciée embarquait 6 locales partielles (arabe, bengali, hindi…) : on ne
 * reprend que le français.
 *
 * Les libellés de **statut de colis** ne sont pas figés ici : le backend les
 * fournit déjà traduits via `GET parcel/all/status`. Ceux ci-dessous ne servent
 * que de repli et aux regroupements propres à l'app.
 */
export const fr = {
  common: {
    appName: 'BeninLink',
    loading: 'Chargement…',
    retry: 'Réessayer',
    cancel: 'Annuler',
    confirm: 'Confirmer',
    save: 'Enregistrer',
    search: 'Rechercher',
    all: 'Tous',
    none: 'Aucun',
    optional: 'optionnel',
  },
  errors: {
    network: 'Connexion au serveur impossible.',
    timeout: 'La requête a expiré. Vérifiez votre connexion.',
    unexpected: 'Une erreur inattendue est survenue.',
    sessionExpired: 'Votre session a expiré. Reconnectez-vous.',
    requiredField: 'Ce champ est obligatoire.',
  },
  auth: {
    signInTitle: 'Connexion',
    phone: 'Téléphone',
    password: 'Mot de passe',
    signIn: 'Se connecter',
    forgotPassword: 'Mot de passe oublié ?',
    /** Le backend identifie par users.unique_id, pas par téléphone. */
    merchantId: 'Identifiant marchand',
    merchantIdHint: "L'identifiant figure sur votre contrat, ce n'est pas votre numéro de téléphone.",
    resetIntro: 'Indiquez votre adresse e-mail : un lien de réinitialisation vous sera envoyé.',
    resetSent: 'Si un compte existe pour cette adresse, un message vient de partir.',
    sendResetLink: 'Envoyer le lien',
    signUpTitle: 'Créer un compte PME',
    companyName: "Nom de l'entreprise",
    managerName: 'Nom du gérant',
    city: 'Ville',
    email: 'E-mail',
    ifu: 'IFU',
    rccm: 'RCCM',
    cnss: 'CNSS',
    otpTitle: 'Code de vérification',
    otpSent: 'Un code vous a été envoyé par SMS au',
    otpResent: 'Un nouveau code vient d\'être envoyé.',
    resendOtp: 'Renvoyer le code',
    verify: 'Vérifier',
    createAccount: 'Créer mon compte',
    forgotTitle: 'Mot de passe oublié',
    noAccount: 'Pas encore de compte ? S\'inscrire',
    /** Les identifiants légaux sont exigés par le backend depuis le chantier 2. */
    legalSection: 'Identifiants légaux de l\'entreprise',
  },
  dashboard: {
    title: 'Tableau de bord',
    balanceToSettle: 'Net à reverser',
    clearableParcels: 'Colis à régler',
    recentParcels: 'Colis récents',
    totalParcels: 'Colis au total',
    totalSales: 'Ventes encaissées',
  },
  parcels: {
    title: 'Mes colis',
    tabOngoing: 'En cours',
    tabDelivered: 'Livrés',
    tabReturns: 'Retours',
    empty: 'Aucun colis pour le moment.',
    newParcel: 'Nouveau colis',
    timeline: 'Suivi',
    detail: 'Détail du colis',
    recipient: 'Destinataire',
    name: 'Nom',
    address: 'Adresse de livraison',
    deliveryType: 'Type de livraison',
    weight: 'Poids',
    amounts: 'Montants',
    cashCollection: 'À encaisser (COD)',
    codFee: 'Frais COD',
    currentPayable: 'Net à reverser',
    shop: "Boutique d'expédition",
    category: 'Catégorie',
    sellingPrice: 'Prix de vente',
    invoiceNo: 'N° de facture',
    create: 'Créer le colis',
    chooseAllOptions: 'Choisissez la boutique, la catégorie et le type de livraison.',
    invalidAmount: 'Montant invalide.',
    /** Rappel du principe posé par la correction de S2. */
    amountsComputedByServer:
      'Les frais, la TVA et le net à reverser sont calculés par BeninLink après création.',
  },
  /** Les 7 étapes marchand (voir src/domain/parcelStatus.ts). */
  parcelStage: {
    pending: 'En attente',
    pickup_assigned: 'Ramassage assigné',
    warehouse: 'Entrepôt',
    courier_assigned: 'Livreur assigné',
    delivered: 'Livré',
    partial: 'Livraison partielle',
    returned: 'Retour',
  },
  wallet: {
    title: 'Portefeuille',
    balance: 'Solde du portefeuille',
    recharge: 'Recharger',
    withdraw: 'Retrait',
    history: 'Historique',
    /** Tant que FedaPay n'est pas branché, ce solde ne bouge pas. */
    prepaidNotice: 'Le rechargement Mobile Money sera disponible prochainement.',
  },
  invoices: {
    title: 'Factures',
    settlementStatement: 'Relevé de règlement',
    codCollected: 'Encaissé COD',
    fees: 'Frais de livraison',
    vat: 'TVA',
    netPayable: 'Net à reverser',
    totalParcels: 'Colis',
  },
  shops: {
    title: 'Mes boutiques',
    empty: 'Aucune boutique enregistrée.',
    default: 'Par défaut',
  },
  rates: {
    title: 'Tarifs de livraison',
  },
  profile: {
    title: 'Profil',
    signOut: 'Se déconnecter',
    address: 'Adresse',
    hub: 'Agence',
    billingTerms: 'Conditions de facturation',
    returnCharges: 'Frais de retour',
  },
} as const;

export type Translations = typeof fr;
