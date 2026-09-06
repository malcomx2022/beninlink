"""Génère les visuels de l'app marchand (icône, adaptive icon Android, monochrome,
splash, favicon) à partir d'un même motif SVG, rendu par Chromium headless."""
import os, subprocess, struct, sys, zlib

# Usage : CHROME=/chemin/vers/chrome python3 assets/source/generate.py (depuis mobile/).
# ⚠️ CHROME doit désigner un binaire **headless** (headless_shell, ou chrome --headless
# sans interface). Un Chromium complet réserve ~87 px de fenêtre à son interface : la
# capture sort alors tronquée en bas, sans la moindre erreur. Le contrôle `opaque=True`
# des rendus à fond plein refuse ce résultat plutôt que de livrer une icône rognée.
# Même méthode que mobile-livreur/assets/source/generate.py : une seule source, des PNG
# régénérables. La police Sora vient de node_modules.
HERE = os.path.dirname(os.path.abspath(__file__))
APP = os.path.dirname(os.path.dirname(HERE))
SHELL = os.environ.get('CHROME', 'chromium')
FONT = os.path.join(APP, 'node_modules/@expo-google-fonts/sora')
OUT = os.path.dirname(HERE)
TMP = os.path.join(HERE, '.tmp'); os.makedirs(TMP, exist_ok=True)
GREEN, GREEN_DARK, OCRE, WHITE, CREAM = '#12503A', '#0D3B2B', '#E0A63C', '#FFFFFF', '#FBF5E8'

def motif(cx, cy, s, awning=OCRE, front=CREAM, box=OCRE, tape=GREEN, cut=False):
    """La **boutique qui expédie** : un auvent à festons au-dessus d'une devanture, et
    sur le comptoir le colis du livreur — même carré barré d'une bande adhésive.

    Les deux apps se lisent ainsi comme une famille : le livreur, c'est le colis en
    mouvement ; le marchand, c'est là où le colis part. `s` = côté de la devanture.
    `cut` = version monochrome (le colis est évidé, pas peint)."""
    x, y = cx - s * 0.5, cy - s * 0.5
    parts = []

    # Auvent : bandeau plus large que la devanture, bordé de cinq festons.
    # Deux formes plutôt qu'un seul tracé : le bandeau est un rectangle arrondi, les
    # festons un zigzag qui part de son bord gauche et revient par le haut.
    aw, ah = s * 1.16, s * 0.19
    ax, ay = cx - aw / 2, y
    parts.append(f'<rect x="{ax}" y="{ay}" width="{aw}" height="{ah}" rx="{s * 0.05}" fill="{awning}"/>')
    dents, prof = 5, s * 0.085
    dw = aw / dents
    zigzag = ' '.join(f'L {ax + (i + 0.5) * dw} {ay + ah + prof} L {ax + (i + 1) * dw} {ay + ah}' for i in range(dents))
    parts.append(f'<path d="M {ax} {ay + ah} {zigzag} Z" fill="{awning}"/>')

    # Devanture, sous l'auvent, et le colis posé dessus : le carré du livreur en plus
    # petit — c'est le même colis, vu avant le départ.
    fy = ay + ah + s * 0.10
    fh = s - (fy - y)
    devanture = f'x="{x}" y="{fy}" width="{s}" height="{fh}" rx="{s * 0.09}"'
    bs = s * 0.46
    bx, by = cx - bs / 2, fy + fh - bs - s * 0.11
    r, tw = bs * 0.16, bs * 0.17

    if cut:
        # Version monochrome : le colis est **évidé** dans la devanture. Une seule
        # couleur, le dessin vient du trou.
        parts.append(f'<mask id="m"><rect x="0" y="0" width="1024" height="1024" fill="white"/>'
                     f'<rect x="{bx}" y="{by}" width="{bs}" height="{bs}" rx="{r}" fill="black"/></mask>')
        parts.append(f'<rect {devanture} fill="{front}" mask="url(#m)"/>')
    else:
        parts.append(f'<rect {devanture} fill="{front}"/>')
        parts.append(f'<rect x="{bx}" y="{by}" width="{bs}" height="{bs}" rx="{r}" fill="{box}"/>')
        parts.append(f'<rect x="{bx + bs*0.5 - tw/2}" y="{by}" width="{tw}" height="{bs}" fill="{tape}" opacity="0.9"/>')
        parts.append(f'<rect x="{bx}" y="{by + bs*0.5 - tw/2}" width="{bs}" height="{tw}" fill="{tape}" opacity="0.9"/>')
    return '\n'.join(parts)

def svg(body, bg=None, size=1024):
    bgrect = f'<rect width="{size}" height="{size}" fill="{bg}"/>' if bg else ''
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 {size} {size}">{bgrect}{body}</svg>'''

def html(svg_markup, size):
    return f'''<!doctype html><html><head><meta charset="utf-8"><style>
    @font-face{{font-family:Sora;font-weight:700;src:url(file://{FONT}/700Bold/Sora_700Bold.ttf)}}
    @font-face{{font-family:Sora;font-weight:500;src:url(file://{FONT}/500Medium/Sora_500Medium.ttf)}}
    html,body{{margin:0;padding:0;background:transparent;width:{size}px;height:{size}px;overflow:hidden}}
    svg{{display:block}}</style></head><body>{svg_markup}</body></html>'''

def lignes_transparentes_en_bas(path):
    """Nombre de lignes entièrement transparentes au bas d'un PNG RGBA.

    Écrit à la main pour ne dépendre de rien : le script tourne sur la machine de
    quiconque régénère les visuels, pas seulement sur un poste équipé.
    """
    donnees = open(path, 'rb').read()
    pos, idat = 8, b''
    largeur = hauteur = None
    while pos < len(donnees):
        taille = struct.unpack('>I', donnees[pos:pos + 4])[0]
        typ = donnees[pos + 4:pos + 8]
        corps = donnees[pos + 8:pos + 8 + taille]
        if typ == b'IHDR':
            largeur, hauteur, profondeur, couleur = *struct.unpack('>IIBB', corps[:10]), 
            if profondeur != 8 or couleur != 6:
                return 0  # pas du RGBA 8 bits : on ne juge pas
        elif typ == b'IDAT':
            idat += corps
        pos += taille + 12

    brut = zlib.decompress(idat)
    pas = largeur * 4
    precedente = bytearray(pas)
    vides = 0
    for y in range(hauteur):
        debut = y * (pas + 1)
        filtre = brut[debut]
        ligne = bytearray(brut[debut + 1:debut + 1 + pas])
        for i in range(pas):
            a = ligne[i - 4] if i >= 4 else 0
            b = precedente[i]
            c = precedente[i - 4] if i >= 4 else 0
            if filtre == 1:
                ligne[i] = (ligne[i] + a) & 255
            elif filtre == 2:
                ligne[i] = (ligne[i] + b) & 255
            elif filtre == 3:
                ligne[i] = (ligne[i] + (a + b) // 2) & 255
            elif filtre == 4:
                p = a + b - c
                pa, pb, pc = abs(p - a), abs(p - b), abs(p - c)
                ligne[i] = (ligne[i] + (a if pa <= pb and pa <= pc else b if pb <= pc else c)) & 255
        vides = vides + 1 if not any(ligne[3::4]) else 0
        precedente = ligne
    return vides


def render(name, svg_markup, size, opaque=False):
    path = os.path.join(TMP, f'{name}.html')
    open(path, 'w').write(html(svg_markup, size))
    out = os.path.join(OUT, f'{name}.png')
    subprocess.run([SHELL, '--headless', '--no-sandbox', '--disable-gpu', '--hide-scrollbars',
                    '--default-background-color=00000000', '--force-device-scale-factor=1',
                    f'--window-size={size},{size}', f'--screenshot={out}', '--allow-file-access-from-files',
                    f'file://{os.path.abspath(path)}'], check=True, capture_output=True)
    with open(out, 'rb') as h:
        h.seek(16); w, hh = struct.unpack('>II', h.read(8))
    if opaque and (vides := lignes_transparentes_en_bas(out)):
        sys.exit(f'{out} : {vides} lignes transparentes en bas — capture tronquée. '
                 'CHROME désigne-t-il bien un binaire headless ?')
    print(f'{out}: {w}x{hh}, {os.path.getsize(out)} o')

# Halo radial discret, identique à l'app livreur : même fond, deux motifs.
GLOW = f'''<defs><radialGradient id="g" cx="0.35" cy="0.3" r="0.9">
  <stop offset="0" stop-color="#1A6A4E"/><stop offset="1" stop-color="{GREEN}"/></radialGradient></defs>
  <rect width="1024" height="1024" fill="url(#g)"/>'''

# 1. Icône iOS / générique : plein cadre (le système arrondit lui-même).
render('icon', svg(GLOW + motif(512, 512, 400)), 1024, opaque=True)
# 2. Adaptive icon Android : premier plan transparent, motif dans la zone sûre (66 % centraux).
render('android-icon-foreground', svg(motif(512, 512, 330)), 1024)
render('android-icon-background', svg(GLOW), 1024, opaque=True)
render('android-icon-monochrome', svg(motif(512, 512, 330, awning=WHITE, front=WHITE, cut=True)), 1024)
# 3. Splash : motif + marque, sur fond transparent (le vert vient de app.json).
wordmark = f'''<text x="512" y="700" text-anchor="middle" font-family="Sora" font-weight="700" font-size="96" fill="{WHITE}">BeninLink</text>
<text x="512" y="790" text-anchor="middle" font-family="Sora" font-weight="500" font-size="60" letter-spacing="6" fill="{OCRE}">MARCHAND</text>'''
render('splash-icon', svg(motif(512, 420, 300) + wordmark), 1024)
# 4. Favicon web : la boutique seule, sur le vert de la charte.
render('favicon', svg(f'<rect width="48" height="48" rx="10" fill="{GREEN}"/>' + motif(24, 25, 30).replace('1024', '48'), size=48), 48, opaque=True)
