/// <reference types="node" />
/**
 * Filet des parcours Maestro (`mobile/.maestro/`).
 *
 * Les parcours de bout en bout se jouent contre un APK installé et le serveur de
 * recette — jamais en CI. Ils visent l'écran par ses **libellés français** et par
 * des **`testID`** : renommer une chaîne de `src/i18n/fr.ts` ou retirer un
 * `testID` les casserait en silence, et on ne le découvrirait qu'au prochain
 * passage sur téléphone. Ce test les relit (sans bibliothèque YAML : le sous-
 * ensemble écrit dans `.maestro/` est simple et voulu tel) et vérifie :
 *
 *   • chaque sélecteur de texte correspond, comme Maestro le fait (expression
 *     régulière, correspondance entière), à au moins une chaîne de `fr` — sauf
 *     les données que le parcours a lui-même saisies (`inputText`) ;
 *   • chaque `id:` existe comme `testID` dans `app/` ou `src/` (un `ChoiceGroup`
 *     marqué `X` rend ses options `X-0`, `X-1`…) ;
 *   • aucun mot de passe écrit en clair, hormis le mauvais, voulu ;
 *   • chaque parcours vise `app.beninlink.marchand` et porte l'étiquette
 *     `lecture` ou `ecriture` (cette dernière exclue des lancements par défaut).
 */
import * as fs from 'fs';
import * as path from 'path';

import { fr } from './i18n/fr';

const APP_ID = 'app.beninlink.marchand';
const MAUVAIS_MOT_DE_PASSE = 'mauvais-mot-de-passe-maestro';
const RACINE = path.resolve(__dirname, '..');
const MAESTRO = path.join(RACINE, '.maestro');

/** Clés dont la valeur scalaire est un sélecteur de texte. */
const CLES_TEXTE = new Set([
  'tapOn',
  'doubleTapOn',
  'longPressOn',
  'assertVisible',
  'assertNotVisible',
  'text',
  'visible',
  'notVisible',
]);

type Ligne = { cle: string; valeur: string; numero: number };
type Parcours = { chemin: string; nom: string; entete: string[]; lignes: Ligne[] };

function fichiers(dossier: string, filtre: (nom: string) => boolean): string[] {
  return fs.readdirSync(dossier, { withFileTypes: true }).flatMap((entree) => {
    const chemin = path.join(dossier, entree.name);
    if (entree.isDirectory()) {
      return entree.name === 'node_modules' ? [] : fichiers(chemin, filtre);
    }
    return filtre(entree.name) ? [chemin] : [];
  });
}

/** Valeur scalaire YAML : guillemets doubles, simples, ou nue (commentaire retiré). */
function scalaire(brut: string): string {
  const v = brut.trim();
  if (v.startsWith('"')) {
    const fin = /^"((?:[^"\\]|\\.)*)"/.exec(v);
    if (!fin) throw new Error(`Guillemet non fermé : ${v}`);
    return JSON.parse(`"${fin[1]}"`) as string;
  }
  if (v.startsWith("'")) {
    const fin = /^'((?:[^']|'')*)'/.exec(v);
    if (!fin) throw new Error(`Apostrophe non fermée : ${v}`);
    return (fin[1] ?? '').replace(/''/g, "'");
  }
  return v.replace(/\s+#.*$/, '').trim();
}

function lire(chemin: string): Parcours {
  const texte = fs.readFileSync(chemin, 'utf8').split('\n');
  const separateur = texte.findIndex((l) => l.trim() === '---');
  const entete = separateur === -1 ? [] : texte.slice(0, separateur);
  const lignes: Ligne[] = [];
  texte.forEach((ligne, i) => {
    if (i <= separateur || /^\s*#/.test(ligne)) return;
    const m = /^\s*(?:-\s+)?([A-Za-z_]+):(?:\s+(.*))?$/.exec(ligne);
    if (!m || !m[1]) return;
    const valeur = m[2] === undefined || m[2].trim() === '' ? '' : scalaire(m[2]);
    lignes.push({ cle: m[1], valeur, numero: i + 1 });
  });
  return { chemin, nom: path.relative(MAESTRO, chemin), entete, lignes };
}

function chainesFr(valeur: unknown): string[] {
  if (typeof valeur === 'string') return [valeur];
  if (valeur && typeof valeur === 'object') return Object.values(valeur).flatMap(chainesFr);
  return [];
}

function etiquettes(entete: string[]): string[] {
  const debut = entete.findIndex((l) => /^tags:/.test(l));
  if (debut === -1) return [];
  const enLigne = /^tags:\s*\[(.*)\]/.exec(entete[debut] ?? '');
  if (enLigne) return (enLigne[1] ?? '').split(',').map((e) => scalaire(e));
  const suite: string[] = [];
  for (const l of entete.slice(debut + 1)) {
    const m = /^\s+-\s+(.+)$/.exec(l);
    if (!m || !m[1]) break;
    suite.push(scalaire(m[1]));
  }
  return suite;
}

const tousLesYaml = fichiers(MAESTRO, (n) => n.endsWith('.yaml'));
const parcours = tousLesYaml.filter((c) => path.basename(c) !== 'config.yaml').map(lire);
const parcoursPrincipaux = parcours.filter((p) => p.nom.startsWith(`flows${path.sep}`));
const libelles = chainesFr(fr);

/** `testID` déclarés dans le code de l'app, et préfixes des `ChoiceGroup`. */
const sources = [...fichiers(path.join(RACINE, 'app'), (n) => /\.tsx?$/.test(n)),
  ...fichiers(path.join(RACINE, 'src'), (n) => /\.tsx?$/.test(n) && !/\.test\.tsx?$/.test(n))]
  .map((c) => fs.readFileSync(c, 'utf8'));
const testIds = sources.flatMap((code) => [
  ...[...code.matchAll(/testID="([^"]+)"/g)].map((m) => m[1] ?? ''),
  // Gabarit : `parcel-card-${parcel.id}` devient l'exemple `parcel-card-0`.
  ...[...code.matchAll(/testID=\{`([^`]+)`\}/g)].map((m) => (m[1] ?? '').replace(/\$\{[^}]*\}/g, '0')),
]);
const prefixesChoix = sources.flatMap((code) =>
  [...code.matchAll(/<ChoiceGroup\b([\s\S]*?)\/>/g)].flatMap((m) =>
    [...(m[1] ?? '').matchAll(/testID="([^"]+)"/g)].map((t) => t[1] ?? ''),
  ),
);

function correspondEntier(motif: string, valeur: string): boolean {
  return new RegExp(`^(?:${motif})$`, 's').test(valeur);
}

describe('parcours Maestro de l’app marchand', () => {
  it('trouve la configuration et les parcours attendus', () => {
    expect(fs.existsSync(path.join(MAESTRO, 'config.yaml'))).toBe(true);
    expect(parcoursPrincipaux.length).toBeGreaterThanOrEqual(7);
    expect(parcours.some((p) => p.nom === path.join('subflows', 'connexion.yaml'))).toBe(true);
  });

  it('exclut par défaut les parcours qui écrivent en recette', () => {
    const config = fs.readFileSync(path.join(MAESTRO, 'config.yaml'), 'utf8');
    expect(config).toMatch(/^excludeTags:\s*\n\s+-\s+ecriture\s*$/m);
  });

  it('chaque fichier vise l’app marchand', () => {
    const fautifs = parcours
      .filter((p) => !p.entete.some((l) => l.trim() === `appId: ${APP_ID}`))
      .map((p) => p.nom);
    expect(fautifs).toEqual([]);
  });

  it('chaque parcours porte l’étiquette lecture ou ecriture', () => {
    const fautifs = parcoursPrincipaux
      .filter((p) => !etiquettes(p.entete).some((e) => e === 'lecture' || e === 'ecriture'))
      .map((p) => p.nom);
    expect(fautifs).toEqual([]);
  });

  it('chaque sélecteur de texte correspond à un libellé de fr.ts', () => {
    const orphelins: string[] = [];
    let verifies = 0;
    for (const p of parcours) {
      // Une donnée saisie par le parcours n'a pas à figurer dans fr.ts.
      const saisies = new Set(p.lignes.filter((l) => l.cle === 'inputText').map((l) => l.valeur));
      for (const l of p.lignes) {
        if (!CLES_TEXTE.has(l.cle) || l.valeur === '' || l.valeur.includes('${')) continue;
        if (saisies.has(l.valeur)) continue;
        verifies += 1;
        let trouve = false;
        try {
          trouve = libelles.some((libelle) => correspondEntier(l.valeur, libelle));
        } catch {
          trouve = false; // expression invalide : Maestro la refuserait aussi
        }
        if (!trouve) orphelins.push(`${p.nom}:${l.numero} « ${l.valeur} »`);
      }
    }
    expect(orphelins).toEqual([]);
    // Garde contre un filet vide : la lecture des fichiers a bien trouvé des sélecteurs.
    expect(verifies).toBeGreaterThan(20);
  });

  it('chaque id visé existe comme testID dans app/ ou src/', () => {
    const absents: string[] = [];
    const ids = parcours.flatMap((p) => p.lignes.filter((x) => x.cle === 'id').map((l) => ({ p, l })));
    expect(ids.length).toBeGreaterThan(10);
    for (const { p, l } of ids) {
      const declare =
        testIds.some((id) => correspondEntier(l.valeur, id)) ||
        prefixesChoix.some(
          (prefixe) => l.valeur.startsWith(`${prefixe}-`) && /^\d+$/.test(l.valeur.slice(prefixe.length + 1)),
        ) ||
        prefixesChoix.some((prefixe) => correspondEntier(l.valeur, `${prefixe}-0`));
      if (!declare) absents.push(`${p.nom}:${l.numero} « ${l.valeur} »`);
    }
    expect(absents).toEqual([]);
  });

  it('aucun identifiant ni mot de passe n’est écrit en clair', () => {
    const fautes: string[] = [];
    for (const p of parcours) {
      const brut = fs.readFileSync(p.chemin, 'utf8');
      if (/^\s*MAESTRO_(MOT_DE_PASSE|IDENTIFIANT)\s*:/m.test(brut)) {
        fautes.push(`${p.nom} : définit un identifiant de recette`);
      }
      let dernierId = '';
      for (const l of p.lignes) {
        if (l.cle === 'id') dernierId = l.valeur;
        if (l.cle !== 'inputText') continue;
        if (/mot-de-passe|password/i.test(dernierId)) {
          const permis = l.valeur === '${MAESTRO_MOT_DE_PASSE}' || l.valeur === MAUVAIS_MOT_DE_PASSE;
          if (!permis) fautes.push(`${p.nom}:${l.numero} mot de passe en clair`);
        }
        if (/identifiant/i.test(dernierId) && l.valeur !== '${MAESTRO_IDENTIFIANT}') {
          fautes.push(`${p.nom}:${l.numero} identifiant en clair`);
        }
      }
    }
    expect(fautes).toEqual([]);
  });
});
