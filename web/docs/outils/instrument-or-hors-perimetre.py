#!/usr/bin/env python3
"""
S61 — LE DETECTEUR DU `OR` QUI SORT DU PERIMETRE.

Pourquoi un SECOND instrument. `instrument-lectures-nues.py` cherche une
LECTURE de la forme `Model::find($request->x)`. Le piege du `orWhere` n'en est
pas une : c'est une STRUCTURE DE REQUETE. L'ecrire dans le premier outil aurait
melange deux familles et rendu sa sortie illisible.

    Model::companywise()->where(A)->orWhere(B)
    SQL : company_id = X AND A OR B          <- le OR de PREMIER NIVEAU s'echappe

`AND` lie plus fort que `OR`. Un `orWhere` pose au meme niveau que la portee la
neutralise donc pour toute la branche de droite. Le groupe de `OR` doit vivre
dans une FERMETURE : `->where(function ($q) { $q->where(A)->orWhere(B); })`.

Deux occurrences deja : S57 (`ParcelRepository::parcelSearchs`) et S60
(`FundTransferRepository::fundTransferSearch`). Dans les deux cas
`companywise()` ETAIT LA — ce n'est pas une garde manquante, c'est une garde que
la structure de la requete annule.

CE QUE L'INSTRUMENT FAIT. Dans chaque methode, il repere un appel de portee
(`companywise()` ou `where('company_id', ...)`), puis avance en comptant les
parentheses et les accolades. Un `->orWhere...` rencontre a la MEME profondeur
que la portee est signale. Un `orWhere` plus profond (dans une fermeture ou un
`whereHas`) ne l'est pas : il est enferme, donc sans danger.

CE QU'IL NE FAIT PAS. Il ne dit pas qu'une occurrence est une faille : un
`orWhere` de premier niveau sur une colonne NON discriminante (un statut, une
date) elargit le resultat DANS le perimetre sans en sortir... non — il en sort
toujours. Mais il peut porter sur une relation qui reste bornee autrement. Le
tri reste humain ; l'instrument fournit la liste, pas le verdict.
"""
import os, re

PORTEE = re.compile(r"companywise\s*\(\s*\)|where\s*\(\s*['\"]company_id['\"]")
OR_APPEL = re.compile(r"->\s*(orWhere[A-Za-z]*)\s*\(")

def sans_commentaires(src):
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    return re.sub(r'//[^\n]*', '', src)

def methodes(src):
    res = []
    for m in re.finditer(r'function\s+(\w+)\s*\([^)]*\)[^{;]*\{', src):
        i = m.end() - 1
        prof = 0
        for j in range(i, len(src)):
            if src[j] == '{':
                prof += 1
            elif src[j] == '}':
                prof -= 1
                if prof == 0:
                    res.append((m.group(1), src[i:j]))
                    break
    return res

def profondeur(corps, position):
    """Profondeur de parentheses + accolades a cette position."""
    p = 0
    for c in corps[:position]:
        if c in '({':
            p += 1
        elif c in ')}':
            p -= 1
    return p

suspects = []
for racine in ('app/Http', 'app/Repositories', 'app/Services', 'app/Models'):
    for d, _, fs in os.walk(racine):
        for f in fs:
            if not f.endswith('.php'):
                continue
            chemin = os.path.join(d, f)
            src = sans_commentaires(open(chemin, encoding='utf-8', errors='ignore').read())
            for nom, corps in methodes(src):
                portees = [(m.start(), profondeur(corps, m.start())) for m in PORTEE.finditer(corps)]
                if not portees:
                    continue
                for mo in OR_APPEL.finditer(corps):
                    d_or = profondeur(corps, mo.start())
                    # une portee posee AVANT, a la MEME profondeur : le OR l'annule
                    for pos, d_p in portees:
                        if pos < mo.start() and d_p == d_or:
                            extrait = corps[mo.start():mo.start() + 70].replace('\n', ' ')
                            extrait = re.sub(r'\s+', ' ', extrait).strip()
                            suspects.append((chemin, nom, mo.group(1), extrait))
                            break

print(f"OR HORS PERIMETRE — {len(suspects)} occurrences au MEME niveau qu'une portee\n")
par_fichier = {}
for c, n, op, e in suspects:
    par_fichier.setdefault(c, []).append((n, op, e))
for c in sorted(par_fichier):
    print(c)
    for n, op, e in par_fichier[c]:
        print(f"    {n}()  ->  {e[:96]}")
