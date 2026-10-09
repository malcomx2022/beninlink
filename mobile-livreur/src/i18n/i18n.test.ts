/**
 * Les libellés de l'app livreur : `t()` suit le chemin pointé dans `fr.ts`, rend la clé elle-même
 * plutôt que de planter si elle manque, et le catalogue n'a ni chaîne vide ni chaîne rognable — un
 * libellé vide afficherait un bouton ou une erreur muets.
 */
import { fr } from './fr';
import { stageLabel, t, type TranslationKey } from './index';

function feuilles(noeud: unknown, chemin = ''): [string, unknown][] {
  if (noeud && typeof noeud === 'object') {
    return Object.entries(noeud as Record<string, unknown>).flatMap(([cle, valeur]) =>
      feuilles(valeur, chemin ? `${chemin}.${cle}` : cle),
    );
  }
  return [[chemin, noeud]];
}

describe('t()', () => {
  it('rend le libellé français pointé par la clé', () => {
    expect(t('auth.signIn')).toBe('Se connecter');
    expect(t('status.partial')).toBe('Livraison partielle');
    expect(t('errors.invalidAmount')).toBe('Saisissez un montant entier en FCFA.');
  });

  it('rend la clé elle-même pour une clé absente', () => {
    expect(t('auth.inexistante' as TranslationKey)).toBe('auth.inexistante');
    expect(t('rien.du.tout' as TranslationKey)).toBe('rien.du.tout');
  });

  it("rend la clé quand elle désigne un groupe et non un libellé", () => {
    expect(t('auth' as TranslationKey)).toBe('auth');
  });

  it('garde le gabarit {n} que les écrans remplacent', () => {
    expect(t('errors.passwordTooShort')).toContain('{n}');
    expect(t('profile.passwordTooShort').replace('{n}', '8')).toBe('Le mot de passe doit contenir au moins 8 caractères.');
  });

  it('nomme les sept étapes marchand', () => {
    expect(stageLabel('pending')).toBe('En attente');
    expect(stageLabel('courier_assigned')).toBe('Livreur assigné');
    expect(stageLabel('returned')).toBe('Retour');
  });
});

describe('catalogue fr', () => {
  const toutes = feuilles(fr);

  it('ne contient que des chaînes', () => {
    expect(toutes.length).toBeGreaterThan(100);
    expect(toutes.filter(([, v]) => typeof v !== 'string')).toEqual([]);
  });

  it("n'a aucune chaîne vide ni entourée d'espaces", () => {
    expect(toutes.filter(([, v]) => typeof v === 'string' && v.trim() === '')).toEqual([]);
    expect(toutes.filter(([, v]) => typeof v === 'string' && v !== v.trim())).toEqual([]);
  });

  it('rend chaque libellé par t()', () => {
    for (const [cle, valeur] of toutes) {
      expect(t(cle as TranslationKey)).toBe(valeur);
    }
  });
});
