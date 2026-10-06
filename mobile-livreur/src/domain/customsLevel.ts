/**
 * Niveaux et statuts d'alerte douanière — **S95**, repris de l'app marchand (S68).
 *
 * ⚠️ Les valeurs numériques appartiennent au contrat d'API — elles ne se
 * redéfinissent pas côté app (règle d'or : `web/` est le contrat). Elles
 * recopient `web/app/Enums/CustomsLevel.php` et `CustomsAlertStatus.php`.
 */

/** `web/app/Enums/CustomsLevel.php`, à l'identique. */
export const CustomsLevel = {
  /** Document recommandé — la livraison se fait sans. */
  INFO: 1,
  /** Document obligatoire — le colis part, mais risque un blocage en douane. */
  WARNING: 2,
  /** Livraison interdite sans le document. */
  BLOCKING: 3,
} as const;

/** `web/app/Enums/CustomsAlertStatus.php`, à l'identique. */
export const CustomsAlertStatus = {
  PENDING: 1,
  RESOLVED: 2,
} as const;

/**
 * Le **nom de charte** de la couleur d'un niveau — pas la couleur elle-même :
 * `colors.ts` réserve `danger` au niveau BLOQUANT, `warning` à l'AVERTISSEMENT,
 * `info` à l'INFO. Un niveau inconnu est traité comme au moins aussi grave que
 * BLOQUANT : on n'atténue jamais.
 */
export function customsLevelColorName(level: number): 'danger' | 'warning' | 'info' {
  if (level >= CustomsLevel.BLOCKING) return 'danger';
  if (level === CustomsLevel.WARNING) return 'warning';
  return 'info';
}
