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
      'Les frais, la TVA et le net à reverser sont calculés par BeninLink, jamais par l\'application.',
    quoteTitle: 'Devis',
    quotePending: 'Calcul du devis…',
    quoteHint: 'Choisissez la catégorie et le type de livraison pour obtenir le devis.',
    totalCharges: 'Total des frais (TVA comprise)',
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
    prepaidNotice: 'Rechargeable par Mobile Money.',
    prepaidExplanation:
      'Ce solde sert à régler vos frais de livraison. Il est distinct du net à reverser.',
    amountToAdd: 'Montant à recharger (FCFA)',
    rechargeAction: 'Payer par Mobile Money',
    operators: 'MTN MoMo et Moov Money — vous choisirez votre opérateur à l\'étape suivante.',
    invalidAmount: 'Saisissez un montant entier supérieur à zéro.',
    rechargeApproved: 'Paiement confirmé. Votre solde a été mis à jour.',
    /** Le crédit dépend du webhook signé : ne jamais annoncer un solde à jour trop tôt. */
    rechargePending:
      'Paiement en cours de confirmation par votre opérateur. Votre solde sera mis à jour automatiquement.',
    confirmationNotice:
      'Le solde n\'est crédité qu\'après confirmation de l\'opérateur, jamais au retour de la page de paiement.',
  },
  invoices: {
    title: 'Factures',
    settlementStatement: 'Relevé de règlement',
    codCollected: 'Encaissé COD',
    fees: 'Frais de livraison',
    vat: 'TVA',
    netPayable: 'Net à reverser',
    totalParcels: 'Colis',
    currentStatement: 'Relevé en cours',
    issued: 'Relevés émis',
    empty: 'Aucun relevé émis pour le moment.',
    tapForDetail: 'Touchez un relevé pour voir sa ventilation.',
    returnFees: 'Frais de retour',
    /** Le PDF attend le chantier 4 côté web/ : ne rien promettre ici. */
    exportNotice: 'Export PDF et CSV disponibles depuis votre espace web.',
  },
  shops: {
    title: 'Mes boutiques',
    empty: 'Aucune boutique enregistrée.',
    default: 'Par défaut',
  },
  customs: {
    title: 'Alertes douanières',
    subtitle: 'Export UEMOA / CEDEAO',
    tabPending: 'En cours',
    tabResolved: 'Traitées',
    empty: 'Aucune alerte douanière.',
    requiredDocument: 'Document requis',
    markResolved: 'Marquer traitée',
    resolvedOn: 'Traitée le',
    /** Champs d'export sur l'écran de création. */
    destination: 'Pays de destination',
    goodsCategory: 'Catégorie de marchandise',
    domestic: 'Bénin (national)',
    exportNotice:
      'Choisissez un pays pour un envoi à l\'export. Les règles douanières s\'appliquent alors.',
    categoryRequired: 'Choisissez la catégorie de marchandise pour un envoi à l\'export.',
    blockingTitle: 'Envoi interdit sans ce document',
    /** Les trois niveaux du DAT, en repli : le backend les fournit traduits. */
    level1: 'Info',
    level2: 'Avertissement',
    level3: 'Bloquant',
  },
  rates: {
    title: 'Tarifs de livraison',
    subtitle: 'Par poids et par zone (FCFA)',
    empty: 'Aucun tarif défini pour votre compte.',
    weight: 'Poids',
    codTitle: "Frais d'encaissement (COD)",
    codNotice: 'Pourcentage prélevé sur le montant encaissé auprès du destinataire.',
    notice:
      'Le tarif retenu dépend de la catégorie, du poids saisi et de la zone de destination. Le montant exact est calculé par BeninLink à la création du colis.',
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
