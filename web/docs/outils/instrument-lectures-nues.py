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
# S58 — la garde ecrite LITTERALEMENT.
#
# Certains depots bornent sans passer par le scope : `HubRepository::filter`,
# `UserRepository::filter` et `DeliveryManRepository::filter` ecrivent
# `where('company_id', settings()->id)` a la main. C'est une garde, et la liste
# ci-dessus ne la reconnaissait pas.
#
# ⚠️ EFFET MESURE SUR CET INSTRUMENT : AUCUN — 35 occurrences avant, 35 apres.
# Les trois depots concernes ne figuraient pas dans sa liste, parce qu'il
# cherche une LECTURE (`Model::find($request->x)`), pas un filtre `like`. Le
# motif est garde parce qu'il est juste, pas parce qu'il a trouve quelque chose.
# Il a en revanche ete decisif pour la mesure de S58 : 13 fausses alertes sur 13.
#
# ⚠️ ON NE PEUT PAS chercher la simple chaine `company_id` : une methode qui lit
# `$request->company_id` la contiendrait et passerait pour gardee.
#
# ⚠️ ET PAS DAVANTAGE `'company_id' => settings()->id` : je l'avais ajoute ici,
# a tort, et il a fallu regarder ce qu'il retirait pour le voir. Cette forme
# n'est pas une portee, c'est un CHAMP ECRIT dans un tableau de creation. Elle
# absolvait neuf lectures nues des passerelles de paiement — dont
# `Merchant::find($request->merchantId)` dans `stripePost()`, qui reste nue.
# Un marqueur de garde qui se trompe ne fait pas du bruit : il fait SILENCE.
GARDES_MOTIF = [
    re.compile(r"where\s*\(\s*['\"]company_id['\"]"),
]


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


# S59 — CE QUE LES AIDES DE GARDE DU PROJET COUVRENT.
#
# `contrepartieHorsPerimetre($request)` ne contient pas la chaine `user_id` :
# c'est un APPEL. Un critere qui exige que la garde nomme le champ la declare
# donc inoperante, et signale huit lectures pourtant protegees.
#
# La carte n'est PAS recopiee ici : elle est LUE dans les traits. Le jour ou
# une contrepartie s'ajoute a `GuardsAccountingCounterparties::CONTREPARTIES`,
# l'instrument la connait sans qu'on y touche — et le jour ou elle en sort, il
# recommence a signaler les lectures qu'elle couvrait.
def champs_couverts_par_les_aides():
    champs = set()
    for d, _, fs in os.walk('app/Traits'):
        for f in fs:
            if not f.endswith('.php'):
                continue
            src = open(os.path.join(d, f), encoding='utf-8', errors='ignore').read()
            champs |= set(re.findall(r"'(\w+)'\s*=>\s*\w+::class", src))
    return champs

CHAMPS_DES_AIDES = champs_couverts_par_les_aides()
AIDE = re.compile(r'\w*HorsPerimetre\s*\(')

LECTURE = re.compile(r'\b(' + '|'.join(MODELS) + r')::(find|where|firstWhere)\s*\(\s*([^;)]{0,140})')

suspects=[]
voisinage=[]   # S59 : lecture nue DANS une methode qui garde par ailleurs
for racine in ('app/Http', 'app/Repositories', 'app/Services'):
    for d,_,fs in os.walk(racine):
        for f in fs:
            if not f.endswith('.php'): continue
            p=os.path.join(d,f)
            src=sans_commentaires(open(p,encoding='utf-8',errors='ignore').read())
            for nom, corps in methodes(src):
                # S59 — DEUX resserrements par rapport a la version de S57, qui
                # sautait la methode entiere des qu'une garde y figurait.
                #
                #  1. POSITION : la garde ne compte que si elle PRECEDE la lecture.
                #  2. IDENTIFIANT : elle ne compte que si elle porte sur le MEME
                #     champ de requete. Une garde sur A n'a jamais protege B —
                #     c'est meme la famille de defauts que toute cette serie
                #     poursuit depuis S45 : la ressource est gardee, le second
                #     identifiant ne l'est pas.
                gardes = [corps.find(g) for g in GARDES if g in corps]
                gardes += [m.start() for g in GARDES_MOTIF for m in g.finditer(corps)]
                for mm in LECTURE.finditer(corps):
                    modele, op, arg = mm.group(1), mm.group(2), mm.group(3)
                    champ = re.search(r'(?:\$request|request\(\))->(\w+)', arg)
                    if not champ:
                        continue                  # l'identifiant ne vient pas de la requete
                    if 'company_id' in arg:
                        continue                  # la lecture porte deja son perimetre
                    champ = champ.group(1)
                    protegee = False
                    for g in gardes:
                        if g >= mm.start():
                            continue              # la garde vient APRES : elle n'a rien empeche
                        # la garde doit parler du MEME champ, entre son debut et la lecture
                        entre = corps[g:mm.start()]
                        if champ in entre:
                            protegee = True
                            break
                        # ... ou la garde est une AIDE du projet dont la carte
                        # couvre ce champ (carte lue dans app/Traits).
                        if champ in CHAMPS_DES_AIDES and AIDE.search(entre):
                            protegee = True
                            break
                    (voisinage if protegee else suspects).append(
                        (p, nom, f"{modele}::{op}({arg.strip()[:64]})"))

print(f"INSTRUMENT RAFFINE — {len(suspects)} occurrences decidables\n")
print(f"[S59] lectures nues VOISINES d'une garde, dans la meme methode : {len(voisinage)}]\n")
par_fichier={}
for p,nom,lec in suspects: par_fichier.setdefault(p,[]).append((nom,lec))
for p in sorted(par_fichier):
    print(p)
    for nom,lec in par_fichier[p]: print(f"    {nom}()  ->  {lec}")

print("\n================ [S59] LE VOISINAGE ================")
par_f2={}
for p,nom,lec in voisinage: par_f2.setdefault(p,[]).append((nom,lec))
for p in sorted(par_f2):
    print(p)
    for nom,lec in par_f2[p]: print(f"    {nom}()  ->  {lec}")
