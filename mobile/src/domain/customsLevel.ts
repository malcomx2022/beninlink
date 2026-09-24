/**
 * Niveaux et statuts d'alerte douanière.
 *
 * ⚠️ Les valeurs numériques appartiennent au contrat d'API — elles ne se
 * redéfinissent pas côté app (règle d'or : `web/` est le contrat). Elles
 * recopient `web/app/Enums/CustomsLevel.php` et `CustomsAlertStatus.php`.
 * L'ordre croissant y est significatif : quand plusieurs règles s'appliquent,
 * la plus élevée l'emporte.
 *
 * Ce fichier existe parce que **deux** écrans en ont besoin : la liste des
 * alertes et le tableau de bord, qui signale la plus grave en attente. Même
 * raison que `parcelStatus.ts` — une table de correspondance, un seul endroit.
 */

/** `web/app/Enums/CustomsLevel.php`, à l'identique. */
export const CustomsLevel = {
  /** Document recommandé — la livraison se fait sans. */
  INFO: 1,
  /** Document obligatoire — le colis part, mais risque un blocage en douane. */
  WARNING: 2,
  /** Livraison interdite sans le document — la création du colis est refusée. */
  BLOCKING: 3,
} as const;

/** `web/app/Enums/CustomsAlertStatus.php`, à l'identique. */
export const CustomsAlertStatus = {
  PENDING: 1,
  RESOLVED: 2,
} as const;

/**
 * Le **nom de charte** de la couleur d'un niveau — pas la couleur elle-même.
 *
 * ⚠️ Ce module ne connaît pas le thème, comme les autres de `src/domain/` qui
 * n'importent rien : il rend une clé, l'écran la résout dans `colors`. C'est
 * aussi ce qui rend la correspondance vérifiable de l'extérieur.
 *
 * ⚠️ `colors.ts` nomme explicitement les trois : `danger` « réservé aux erreurs
 * et au niveau douanier BLOQUANT », `warning` « AVERTISSEMENT douanier » et
 * `info` « INFO douanier ». L'écran en utilisait deux AUTRES — l'**ocre**, que
 * la charte réserve aux « actions clés uniquement (bouton principal, montant à
 * retenir) », et le **vert primaire** pour un niveau Info, qui se lit « tout va
 * bien » au lieu de « information ». Une couleur qui ment sur la gravité coûte
 * plus cher qu'une couleur laide.
 *
 * Un niveau inconnu (le backend peut en ajouter, l'ordre est croissant) est
 * traité comme au moins aussi grave que BLOQUANT : on n'atténue jamais.
 */
export function customsLevelColorName(level: number): 'danger' | 'warning' | 'info' {
  if (level >= CustomsLevel.BLOCKING) return 'danger';
  if (level === CustomsLevel.WARNING) return 'warning';
  return 'info';
}

/** Le niveau le plus grave d'un lot, ou `0` si le lot est vide. */
export function highestCustomsLevel(alerts: readonly { level: number }[]): number {
  return alerts.reduce((max, alert) => (alert.level > max ? alert.level : max), 0);
}
