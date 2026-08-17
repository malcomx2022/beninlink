/**
 * Charte BeninLink — partagée avec mobile-livreur/.
 * Toute couleur affichée passe par ce fichier : aucun littéral hexadécimal
 * dans les écrans.
 */
export const colors = {
  /** Vert profond — couleur primaire, en-têtes, éléments actifs. */
  primary: '#12503A',
  primaryDark: '#0D3A2A',
  primaryLight: '#1C6B4E',
  /** Ocre — actions clés uniquement (bouton principal, montant à retenir). */
  accent: '#E0A63C',
  accentDark: '#C08D2A',

  /** Rouge = incident. Réservé aux erreurs et au niveau douanier BLOQUANT. */
  danger: '#C0392B',
  /** Orange = AVERTISSEMENT douanier, retard, livraison partielle. */
  warning: '#D98324',
  /** Vert clair = succès, colis livré. */
  success: '#1E8E5A',
  /** Bleu sobre = INFO douanier, mentions neutres. */
  info: '#2C6E8F',

  text: '#1A1A1A',
  textMuted: '#5F6B66',
  textOnPrimary: '#FFFFFF',

  background: '#F7F9F8',
  surface: '#FFFFFF',
  border: '#E1E6E3',
  disabled: '#B8C2BD',
} as const;

export type ColorName = keyof typeof colors;
