/// <reference types="node" />
/**
 * Filet des flux Maestro (`.maestro/`) : les parcours de bout en bout tournent sur un APK
 * installé, contre la recette, et cherchent les écrans par leurs textes français et leurs
 * `testID`. Rien ne les rejoue dans la CI — un libellé renommé dans `fr.ts` ou un `testID`
 * retiré les casserait en silence, jusqu'à la prochaine recette sur téléphone.
 *
 * Ce test lit chaque `.yaml` sans bibliothèque YAML (lecture ligne à ligne, suffisante pour
 * la forme des flux) et vérifie :
 *  - que chaque sélecteur de texte (`tapOn`, `assertVisible`, `assertNotVisible`, `visible`,
 *    `notVisible`, `element`, `from` en forme courte, et toute clé `text:`) reconnaît au moins
 *    une chaîne de `fr.ts`, comme Maestro : motif en correspondance **complète**, ou égalité
 *    littérale. Un motif de montant doit reconnaître la sortie de `formatAmount()` ;
 *  - que chaque `id:` existe comme `testID` (ou `tabBarButtonTestID`) dans `app/` ou `src/` ;
 *  - qu'aucun mot de passe n'est écrit en clair, hormis le faux mot de passe délibéré ;
 *  - l'en-tête de chaque fichier (`appId`, `tags`) et la configuration (lecture par défaut).
 */
import * as fs from 'fs';
import * as path from 'path';

import { fr } from './i18n/fr';
import { formatAmount } from './domain/money';

const RACINE = path.resolve(__dirname, '..');
const DOSSIER = path.join(RACINE, '.maestro');
const APP_ID = 'app.beninlink.livreur';
const FAUX_MOT_DE_PASSE = 'mauvais-mot-de-passe-maestro';

/** Commandes dont la forme courte (`- tapOn: Texte`) est un sélecteur de texte. */
const SELECTEURS_COURTS = new Set([
  'tapOn',
  'doubleTapOn',
  'longPressOn',
  'assertVisible',
  'assertNotVisible',
  'visible',
  'notVisible',
  'element',
  'from',
]);
const COMMANDES = new Set([...SELECTEURS_COURTS, 'scrollUntilVisible', 'swipe', 'extendedWaitUntil', 'runFlow']);

type Selecteur = { valeur: string; commande: string; fichier: string; ligne: number };
type Flux = {
  relatif: string;
  appId: string | null;
  tags: string[];
  textes: Selecteur[];
  ids: Selecteur[];
  saisies: Selecteur[];
  sousFlux: string[];
  lignes: string[];
};

function lister(dossier: string): string[] {
  return fs
    .readdirSync(dossier, { withFileTypes: true })
    .flatMap((e) => {
      const chemin = path.join(dossier, e.name);
      if (e.isDirectory()) return lister(chemin);
      return e.name.endsWith('.yaml') ? [chemin] : [];
    })
    .sort();
}

/** Valeur scalaire YAML : guillemets simples ou doubles, sinon brute (commentaire final retiré). */
function scalaire(brut: string): string {
  const v = brut.trim();
  if (v.startsWith("'")) {
    const fin = v.lastIndexOf("'");
    return v.slice(1, fin).replace(/''/g, "'");
  }
  if (v.startsWith('"')) {
    const fin = v.lastIndexOf('"');
    return JSON.parse(v.slice(0, fin + 1).replace(/\\(?!["\\/bfnrtu])/g, '\\\\')) as string;
  }
  return v.replace(/\s+#.*$/, '');
}

const estVariable = (v: string) => /\$\{[^}]+\}/.test(v);

function lire(chemin: string): Flux {
  const relatif = path.relative(DOSSIER, chemin);
  const lignes = fs.readFileSync(chemin, 'utf8').split('\n');
  const flux: Flux = { relatif, appId: null, tags: [], textes: [], ids: [], saisies: [], sousFlux: [], lignes };
  const separateur = lignes.findIndex((l) => l.trim() === '---');

  // En-tête : appId et tags (liste en tirets ou en crochets).
  const entete = separateur === -1 ? lignes : lignes.slice(0, separateur);
  entete.forEach((l, i) => {
    const appId = /^appId:\s*(.+)$/.exec(l);
    if (appId?.[1]) flux.appId = scalaire(appId[1]);
    const tags = /^tags:\s*(.*)$/.exec(l);
    if (!tags) return;
    const enLigne = tags[1]?.trim() ?? '';
    if (enLigne.startsWith('[')) {
      flux.tags = enLigne.replace(/[[\]]/g, '').split(',').map((s) => scalaire(s)).filter(Boolean);
      return;
    }
    for (const suite of entete.slice(i + 1)) {
      const item = /^\s+-\s+(.+)$/.exec(suite);
      if (!item?.[1]) break;
      flux.tags.push(scalaire(item[1]));
    }
  });

  // Corps : pile des clés ouvertes, pour savoir à quelle commande appartient un `id:` ou un `text:`.
  const pile: { colonne: number; cle: string }[] = [];
  lignes.slice(separateur + 1).forEach((l, i) => {
    if (/^\s*#/.test(l) || !l.trim()) return;
    const m = /^(\s*)(-\s+)?([A-Za-z_]+):(?:\s+(.*))?$/.exec(l);
    if (!m) return;
    const colonne = (m[1] ?? '').length + (m[2] ?? '').length;
    const cle = m[3] ?? '';
    const reste = (m[4] ?? '').trim();
    while (pile.length && (pile[pile.length - 1]?.colonne ?? 0) >= colonne) pile.pop();
    const parent = [...pile].reverse().find((p) => COMMANDES.has(p.cle))?.cle;
    pile.push({ colonne, cle });
    if (!reste || reste.startsWith('#')) return;

    const valeur = scalaire(reste);
    const s: Selecteur = { valeur, commande: parent ?? cle, fichier: relatif, ligne: separateur + 2 + i };
    if (cle === 'inputText') flux.saisies.push(s);
    else if (cle === 'runFlow' || cle === 'file') flux.sousFlux.push(valeur);
    else if (estVariable(valeur)) return;
    else if (cle === 'id') flux.ids.push(s);
    else if (cle === 'text') flux.textes.push(s);
    else if (SELECTEURS_COURTS.has(cle)) flux.textes.push({ ...s, commande: cle });
  });
  return flux;
}

/** Toutes les chaînes de `fr.ts`, à plat. */
function aplatir(noeud: unknown): string[] {
  if (typeof noeud === 'string') return [noeud];
  if (noeud && typeof noeud === 'object') return Object.values(noeud).flatMap(aplatir);
  return [];
}

/** Comme Maestro : le motif reconnaît le texte entier, ou lui est égal mot pour mot. */
function correspond(motif: string, texte: string): boolean {
  return texte === motif || new RegExp(`^(?:${motif})$`, 's').test(texte);
}

/** Les `testID` de l'app ; un gabarit (`parcel-card-${id}`) donne un exemple (`parcel-card-1`). */
function testIdsDeLApp(): string[] {
  const fichiers = ['app', 'src'].flatMap(function parcourir(d: string): string[] {
    return fs.readdirSync(path.join(RACINE, d), { withFileTypes: true }).flatMap((e) => {
      const rel = path.join(d, e.name);
      if (e.isDirectory()) return parcourir(rel);
      return /\.tsx?$/.test(e.name) && !/\.test\.tsx?$/.test(e.name) ? [rel] : [];
    });
  });
  const motifs = [
    /\btestID=(?:"([^"]+)"|\{\s*'([^']+)'\s*\}|\{\s*"([^"]+)"\s*\}|\{\s*`([^`]+)`\s*\})/g,
    /\btabBarButtonTestID:\s*(?:'([^']+)'|"([^"]+)")/g,
  ];
  return fichiers.flatMap((f) => {
    const source = fs.readFileSync(path.join(RACINE, f), 'utf8');
    return motifs.flatMap((re) =>
      [...source.matchAll(re)].map((m) => (m.slice(1).find(Boolean) ?? '').replace(/\$\{[^}]+\}/g, '1')),
    );
  });
}

const fichiers = lister(DOSSIER);
const config = fichiers.find((f) => path.basename(f) === 'config.yaml');
const tousLesFlux = fichiers.filter((f) => f !== config).map(lire);
const fluxPrincipaux = tousLesFlux.filter((f) => f.relatif.startsWith(`flows${path.sep}`));
const chainesFr = aplatir(fr);
const montants = [0, 1500, 125000, -2500].map((n) => formatAmount(n));
const testIds = testIdsDeLApp();

const textes = tousLesFlux.flatMap((f) => f.textes);
const ids = tousLesFlux.flatMap((f) => f.ids);
const ou = (s: Selecteur) => `${s.fichier}:${s.ligne}`;

describe('flux Maestro de l’app livreur (.maestro/)', () => {
  it('contient la configuration, le sous-flux de connexion et des flux qui citent des textes et des testID', () => {
    expect(config).toBeDefined();
    expect(tousLesFlux.map((f) => f.relatif)).toContain(path.join('subflows', 'connexion.yaml'));
    expect(fluxPrincipaux.length).toBeGreaterThanOrEqual(4);
    // Garde contre un analyseur qui ne trouverait plus rien : le filet serait alors vide.
    expect(textes.length).toBeGreaterThan(20);
    expect(ids.length).toBeGreaterThan(5);
  });

  it('lance flows/*.yaml et exclut par défaut les flux « ecriture »', () => {
    const source = fs.readFileSync(config as string, 'utf8');
    expect(source).toMatch(/^flows:\s*\n\s+-\s+["']?flows\/\*\.yaml["']?\s*$/m);
    expect(source).toMatch(/^excludeTags:\s*\n\s+-\s+["']?ecriture["']?\s*$/m);
  });

  it('vise l’application Android déclarée dans app.json, dans chaque fichier', () => {
    const appJson = JSON.parse(fs.readFileSync(path.join(RACINE, 'app.json'), 'utf8')) as {
      expo: { android: { package: string } };
    };
    expect(appJson.expo.android.package).toBe(APP_ID);
    for (const f of tousLesFlux) expect({ fichier: f.relatif, appId: f.appId }).toEqual({ fichier: f.relatif, appId: APP_ID });
  });

  it('étiquette chaque flux de flows/ « lecture » ou « ecriture »', () => {
    for (const f of fluxPrincipaux) {
      const lectureOuEcriture = f.tags.filter((t) => t === 'lecture' || t === 'ecriture');
      expect({ fichier: f.relatif, tags: lectureOuEcriture.length }).toEqual({ fichier: f.relatif, tags: 1 });
    }
  });

  it('n’appelle que des sous-flux qui existent', () => {
    for (const f of tousLesFlux) {
      for (const cible of f.sousFlux) {
        const chemin = path.resolve(path.dirname(path.join(DOSSIER, f.relatif)), cible);
        expect({ fichier: f.relatif, cible, existe: fs.existsSync(chemin) }).toEqual({ fichier: f.relatif, cible, existe: true });
      }
    }
  });

  it.each(textes.map((s) => [s.valeur, ou(s), s] as const))(
    'le texte « %s » (%s) est un libellé de fr.ts ou un montant de formatAmount()',
    (_valeur, _ou, s) => {
      const reconnus = [...chainesFr, ...montants].filter((texte) => correspond(s.valeur, texte));
      expect(reconnus.length).toBeGreaterThan(0);
    },
  );

  it.each(ids.map((s) => [s.valeur, ou(s), s] as const))(
    'l’identifiant « %s » (%s) est un testID de l’app',
    (_valeur, _ou, s) => {
      expect(testIds.some((id) => correspond(s.valeur, id))).toBe(true);
    },
  );

  it('n’écrit aucun mot de passe en clair, hormis le faux mot de passe délibéré', () => {
    for (const f of tousLesFlux) {
      for (const s of f.saisies) {
        const permis = /^\$\{MAESTRO_[A-Z_]+\}$/.test(s.valeur) || s.valeur === FAUX_MOT_DE_PASSE;
        expect({ ou: ou(s), saisie: s.valeur, permis }).toEqual({ ou: ou(s), saisie: s.valeur, permis: true });
      }
      // Pas de variable « mot de passe » définie en dur dans un bloc env: non plus.
      f.lignes.forEach((l, i) => {
        const def = /^\s*([A-Za-z_]*(?:MOT_DE_PASSE|PASSWORD|PASSWD)[A-Za-z_]*)\s*:\s*(.+)$/i.exec(l);
        if (def?.[2]) expect({ ou: `${f.relatif}:${i + 1}`, valeur: estVariable(def[2]) }).toEqual({ ou: `${f.relatif}:${i + 1}`, valeur: true });
      });
    }
    // Le faux mot de passe ne sert qu'au flux de connexion refusée.
    const avecFaux = tousLesFlux.filter((f) => f.saisies.some((s) => s.valeur === FAUX_MOT_DE_PASSE)).map((f) => f.relatif);
    expect(avecFaux).toEqual([path.join('flows', '01-connexion-refusee.yaml')]);
  });

  it('un flux « lecture » ne touche jamais « Enregistrer » (aucune déclaration envoyée)', () => {
    for (const f of fluxPrincipaux.filter((x) => x.tags.includes('lecture'))) {
      const touches = f.textes.filter((s) => s.commande === 'tapOn' && correspond(s.valeur, fr.status.confirm));
      expect({ fichier: f.relatif, touches: touches.map(ou) }).toEqual({ fichier: f.relatif, touches: [] });
    }
  });
});
