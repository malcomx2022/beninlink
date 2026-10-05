/**
 * S84 (M2) — les montants FCFA, miroir de `formatAmount()` de `web/`.
 * Entiers, espace insécable comme séparateur de milliers, symbole suffixé.
 */
import { CURRENCY_SYMBOL, formatAmount, formatRate, toAmount } from './money';

const NBSP = '\u00A0';

describe('toAmount', () => {
  it('arrondit un nombre à l\'entier : le franc CFA n\'a pas de subdivision', () => {
    expect(toAmount(1234)).toBe(1234);
    expect(toAmount(1234.4)).toBe(1234);
    expect(toAmount(1234.5)).toBe(1235);
  });

  it('tolère la chaîne d\'un endpoint sans Resource', () => {
    expect(toAmount('1234.00')).toBe(1234);
    expect(toAmount(' 500 ')).toBe(500);
  });

  it('rend 0 sur une valeur absente ou illisible', () => {
    expect(toAmount(undefined)).toBe(0);
    expect(toAmount(null)).toBe(0);
    expect(toAmount('')).toBe(0);
    expect(toAmount('abc')).toBe(0);
    expect(toAmount(NaN)).toBe(0);
  });
});

describe('formatAmount', () => {
  it('groupe les milliers par une espace insécable et suffixe le symbole', () => {
    expect(formatAmount(1500)).toBe(`1${NBSP}500${NBSP}${CURRENCY_SYMBOL}`);
    expect(formatAmount(1234567)).toBe(`1${NBSP}234${NBSP}567${NBSP}${CURRENCY_SYMBOL}`);
  });

  it('ne groupe rien sous mille et rend zéro sans vide', () => {
    expect(formatAmount(999)).toBe(`999${NBSP}${CURRENCY_SYMBOL}`);
    expect(formatAmount(0)).toBe(`0${NBSP}${CURRENCY_SYMBOL}`);
  });

  it('porte le signe devant le nombre groupé', () => {
    expect(formatAmount(-2500)).toBe(`-2${NBSP}500${NBSP}${CURRENCY_SYMBOL}`);
  });

  it('peut rendre le nombre seul', () => {
    expect(formatAmount(1500, false)).toBe(`1${NBSP}500`);
  });
});

describe('formatRate', () => {
  it('rend un taux entier sans décimales et avec le signe %', () => {
    expect(formatRate(18)).toBe(`18${NBSP}%`);
  });

  it('écrit la décimale à la française, sans zéro de traîne', () => {
    expect(formatRate(5.5)).toBe(`5,5${NBSP}%`);
    expect(formatRate(2.25)).toBe(`2,25${NBSP}%`);
    expect(formatRate('7.50')).toBe(`7,5${NBSP}%`);
  });

  it('ne porte jamais le symbole monétaire et rabat l\'illisible sur 0', () => {
    expect(formatRate('abc')).toBe(`0${NBSP}%`);
    expect(formatRate(undefined)).toBe(`0${NBSP}%`);
    expect(formatRate(18)).not.toContain(CURRENCY_SYMBOL);
  });
});
