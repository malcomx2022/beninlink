/**
 * Montants en XOF (FCFA).
 *
 * Miroir de `formatAmount()` / `formatRate()` de web/app/Http/Helper/Helper.php :
 * le franc CFA n'a pas de subdivision, tout montant est un ENTIER, le séparateur
 * de milliers est une espace insécable et le symbole est suffixé.
 * Si la mise en forme change côté backend, changer ici aussi — les deux doivent
 * rester cohérents à l'écran.
 */

const NBSP = ' ';
export const CURRENCY_SYMBOL = 'FCFA';

/**
 * Normalise une valeur venue de l'API en entier.
 *
 * Depuis le 2026-08-16 l'API renvoie des entiers JSON (`1234`) et non plus des
 * chaînes (`"1234.00"`). On tolère malgré tout la chaîne : certains endpoints ne
 * passent pas par un Resource et pourraient encore en produire.
 */
export function toAmount(value: unknown): number {
  // S84 — un nombre non fini (NaN, Infinity) rendait `NaN`, donc « NaN FCFA » à l'écran.
  if (typeof value === 'number') return Number.isFinite(value) ? Math.round(value) : 0;
  if (typeof value === 'string' && value.trim() !== '') {
    const n = Number(value);
    return Number.isFinite(n) ? Math.round(n) : 0;
  }
  return 0;
}

/** « 1 500 FCFA ». Passer `false` pour n'obtenir que le nombre. */
export function formatAmount(value: unknown, withCurrency = true): string {
  const n = toAmount(value);
  const grouped = Math.abs(n)
    .toString()
    .replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
  const signed = n < 0 ? `-${grouped}` : grouped;
  return withCurrency ? `${signed}${NBSP}${CURRENCY_SYMBOL}` : signed;
}

/**
 * Taux en pourcentage (TVA marchand) : « 18 % », « 5,5 % ».
 * Un taux n'est pas un montant et ne porte jamais le symbole monétaire —
 * le socle We Courier faisait cette confusion, corrigée côté web.
 */
export function formatRate(value: unknown): string {
  const n = typeof value === 'number' ? value : Number(value ?? 0);
  const safe = Number.isFinite(n) ? n : 0;
  const text = Number.isInteger(safe)
    ? safe.toString()
    : safe.toFixed(2).replace('.', ',').replace(/,?0+$/, '');
  return `${text}${NBSP}%`;
}
