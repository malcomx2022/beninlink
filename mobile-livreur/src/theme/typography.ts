/**
 * Sora pour les titres et les chiffres (montants, compteurs),
 * DM Sans pour le corps de texte. Charte BeninLink.
 *
 * Les fichiers de polices sont chargés par expo-font dans app/_layout.tsx ;
 * les clés ci-dessous doivent correspondre aux noms d'enregistrement.
 */
export const fonts = {
  /** Titres d'écran et de section. */
  heading: 'Sora_600SemiBold',
  headingBold: 'Sora_700Bold',
  /** Chiffres : montants FCFA, KPIs, compteurs. Sora par choix de charte. */
  numeric: 'Sora_600SemiBold',
  /** Corps de texte, libellés, champs de saisie. */
  body: 'DMSans_400Regular',
  bodyMedium: 'DMSans_500Medium',
} as const;

export const fontSizes = {
  xs: 11,
  sm: 13,
  md: 15,
  lg: 18,
  xl: 22,
  xxl: 28,
  /** Solde de portefeuille, net à reverser. */
  display: 34,
} as const;

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
} as const;

export const radii = {
  sm: 6,
  md: 10,
  lg: 16,
  pill: 999,
} as const;
