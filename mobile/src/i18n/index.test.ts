/**
 * i18n (`index.ts`, `fr.ts`) — toute chaîne visible passe par `t()` et se lit en français.
 *
 * Garde : `t()` résout une clé à points vers son libellé ; une clé absente ou qui
 * désigne une section rend la clé elle-même au lieu de planter (S113 : aucune clé
 * affichée brute ne doit pour autant exister dans le catalogue) ; `t()` n'interpole
 * pas — les écrans remplacent eux-mêmes `{n}` ; le catalogue n'a aucune chaîne vide.
 */
import { fr } from './fr';
import { stageLabel, t, type TranslationKey } from './index';

function feuilles(objet: object, prefixe = ''): [string, unknown][] {
  return Object.entries(objet).flatMap(([cle, valeur]) => {
    const chemin = prefixe ? `${prefixe}.${cle}` : cle;
    return valeur && typeof valeur === 'object' ? feuilles(valeur, chemin) : [[chemin, valeur] as [string, unknown]];
  });
}

describe('t()', () => {
  it('résout une clé à points vers son libellé français', () => {
    expect(t('auth.signIn')).toBe('Se connecter');
    expect(t('parcels.tabDelivered')).toBe('Livrés');
  });

  it('rend la clé elle-même quand elle est absente, sans planter', () => {
    const absente = 'auth.cleQuiNExistePas' as TranslationKey;
    const profonde = 'inconnu.sous.cle' as TranslationKey;
    expect(t(absente)).toBe('auth.cleQuiNExistePas');
    expect(t(profonde)).toBe('inconnu.sous.cle');
  });

  it("rend la clé quand elle désigne une section et non un libellé", () => {
    expect(t('auth' as TranslationKey)).toBe('auth');
  });

  it("n'interpole pas : le gabarit `{n}` est rendu tel quel, l'écran le remplace", () => {
    expect(t('errors.passwordTooShort')).toContain('{n}');
    expect(t('errors.passwordTooShort').replace('{n}', '8')).toBe(
      'Le mot de passe doit contenir au moins 8 caractères.',
    );
  });

  it("stageLabel passe par t() pour chaque étape marchand", () => {
    for (const etape of Object.keys(fr.parcelStage) as (keyof typeof fr.parcelStage)[]) {
      expect(stageLabel(etape)).toBe(fr.parcelStage[etape]);
    }
  });
});

describe('catalogue fr', () => {
  const toutes = feuilles(fr);

  it('ne contient que des chaînes, aucune vide', () => {
    expect(toutes.length).toBeGreaterThan(100);
    const fautives = toutes.filter(([, v]) => typeof v !== 'string' || v.trim() === '').map(([k]) => k);
    expect(fautives).toEqual([]);
  });

  it('chaque libellé se résout par t() vers lui-même (aucune clé affichée brute)', () => {
    const brutes = toutes.filter(([cle, v]) => t(cle as TranslationKey) !== v).map(([k]) => k);
    expect(brutes).toEqual([]);
  });

  it("n'a pas d'espace parasite en tête ou en fin de libellé", () => {
    const fautives = toutes.filter(([, v]) => typeof v === 'string' && v !== v.trim()).map(([k]) => k);
    expect(fautives).toEqual([]);
  });
});
