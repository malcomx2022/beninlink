#!/usr/bin/env python3
"""
S57 — l'instrument des lectures nues, affiné jusqu'à devenir décidable.

    python3 docs/outils/instrument-lectures-nues.py     (depuis web/)

## La dette que cet outil paye

S47 avait relevé « ~250 occurrences de lectures non scopées », jugé l'instrument
TROP BRUYANT POUR DÉCIDER, et remis son affinage à plus tard :

    « il faudrait le raffiner — ne retenir qu'une lecture dont AUCUNE garde ne
      domine le chemin — avant d'en tirer un lot. C'est un chantier en soi. »

## Les trois critères, et ce qu'ils retirent

    brut : toute lecture non scopée d'un modèle portant scopeCompanywise   ~570
    + l'identifiant doit venir de la REQUÊTE ($request-> / request()->)      53
    + connaître les AIDES DE GARDE du projet, pas seulement companywise(    43

Le troisième est celui qui rend l'instrument honnête. `contrepartieHorsPerimetre()`
(S48) ne contient pas la chaîne `companywise(` : l'instrument brut ACCUSAIT donc
les dépôts de recette, dépense et salaire, gardés depuis S48.

    > Un instrument qui cherche une FORME ne trouve pas une RÈGLE.
      (leçon de S52, appliquée ici à mon propre outil)

## Ce qu'il ne fait pas

Il ne remplace pas la lecture. Il DÉSIGNE, il ne conclut pas : sur les 43
occurrences de S57, huit étaient derrière le drapeau `online_payout` (D10, à
`false`), quatre relevaient de flux d'authentification globaux par nature (OTP par
e-mail ou mobile), et cinq étaient de vrais défauts — corrigés et prouvés par
`SearchAndBalanceDisclosureTest`.

Le tri reste un travail humain. L'outil le rend possible en le ramenant de 570
lignes à 43.
"""
import re, os, subprocess

MODELS = subprocess.run(
    "grep -rl 'function scopeCompanywise' app/Models/ | xargs -n1 basename | sed 's/\\.php$//' | sort -u",
    shell=True, capture_output=True, text=True).stdout.split()

# ⚠️ Le raffinement qui rend l'instrument DECIDABLE : il doit connaitre les aides
# de garde que le projet s'est donnees. `contrepartieHorsPerimetre()` (S48) ne
# contient pas la chaine `companywise(`, et l'instrument brut accusait donc les
# depots de recette/depense/salaire, qui sont gardes depuis S48.
GARDES = ['companywise(', 'contrepartieHorsPerimetre(', 'identifiantsHorsPerimetre(',
          'catalogueHorsPerimetre(', 'ticketsVisibles(', 'marchandDeLaSociete(',
          'abort_if(', 'abort_unless(']

def sans_commentaires(src):
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    return re.sub(r'//[^\n]*', '', src)

def methodes(src):
    res=[]
    for m in re.finditer(r'function\s+(\w+)\s*\([^)]*\)[^{;]*\{', src):
        i=m.end()-1; prof=0
        for j in range(i, len(src)):
            if src[j]=='{': prof+=1
            elif src[j]=='}':
                prof-=1
                if prof==0:
                    res.append((m.group(1), src[i:j])); break
    return res

LECTURE = re.compile(r'\b(' + '|'.join(MODELS) + r')::(find|where|firstWhere)\s*\(\s*([^;)]{0,140})')

suspects=[]
for racine in ('app/Http', 'app/Repositories', 'app/Services'):
    for d,_,fs in os.walk(racine):
        for f in fs:
            if not f.endswith('.php'): continue
            p=os.path.join(d,f)
            src=sans_commentaires(open(p,encoding='utf-8',errors='ignore').read())
            for nom, corps in methodes(src):
                if any(g in corps for g in GARDES):
                    continue                      # une garde domine la methode
                for mm in LECTURE.finditer(corps):
                    modele, op, arg = mm.group(1), mm.group(2), mm.group(3)
                    if not re.search(r'\$request->|request\(\)->', arg):
                        continue                  # l'identifiant ne vient pas du corps
                    if 'company_id' in arg:
                        continue                  # la lecture porte deja son perimetre
                    suspects.append((p, nom, f"{modele}::{op}({arg.strip()[:64]})"))

print(f"INSTRUMENT RAFFINE — {len(suspects)} occurrences decidables\n")
par_fichier={}
for p,nom,lec in suspects: par_fichier.setdefault(p,[]).append((nom,lec))
for p in sorted(par_fichier):
    print(p)
    for nom,lec in par_fichier[p]: print(f"    {nom}()  ->  {lec}")
