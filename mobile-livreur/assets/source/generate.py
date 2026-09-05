"""Génère les visuels de l'app livreur (icône, adaptive icon Android, monochrome,
splash, favicon) à partir d'un même motif SVG, rendu par Chromium headless."""
import os, subprocess, struct, sys

# Usage : CHROME=/chemin/vers/chrome python3 assets/source/generate.py (depuis mobile-livreur/).
# Chromium / Chrome headless suffit ; la police Sora vient de node_modules.
HERE = os.path.dirname(os.path.abspath(__file__))
APP = os.path.dirname(os.path.dirname(HERE))
SHELL = os.environ.get('CHROME', 'chromium')
FONT = os.path.join(APP, 'node_modules/@expo-google-fonts/sora')
OUT = os.path.dirname(HERE)
TMP = os.path.join(HERE, '.tmp'); os.makedirs(TMP, exist_ok=True)
GREEN, GREEN_DARK, OCRE, WHITE, CREAM = '#12503A', '#0D3B2B', '#E0A63C', '#FFFFFF', '#FBF5E8'

def motif(cx, cy, s, box=OCRE, tape=GREEN, lines=WHITE, cut=False):
    """Colis (carré arrondi) barré d'une bande adhésive, précédé de trois traits de
    vitesse : le coursier en mouvement. `s` = côté du colis. `cut` = version
    monochrome (la bande est évidée, pas peinte)."""
    r = s * 0.16
    x, y = cx - s * 0.5 + s * 0.22, cy - s * 0.5      # colis décalé à droite des traits
    tw = s * 0.16                                    # largeur de la bande
    parts = []
    if cut:
        parts.append(f'''<mask id="m"><rect x="0" y="0" width="1024" height="1024" fill="white"/>
          <rect x="{x + s*0.5 - tw/2}" y="{y}" width="{tw}" height="{s}" fill="black"/>
          <rect x="{x}" y="{y + s*0.5 - tw/2}" width="{s}" height="{tw}" fill="black"/></mask>''')
        parts.append(f'<rect x="{x}" y="{y}" width="{s}" height="{s}" rx="{r}" fill="{box}" mask="url(#m)"/>')
    else:
        parts.append(f'<rect x="{x}" y="{y}" width="{s}" height="{s}" rx="{r}" fill="{box}"/>')
        parts.append(f'<rect x="{x + s*0.5 - tw/2}" y="{y}" width="{tw}" height="{s}" fill="{tape}" opacity="0.9"/>')
        parts.append(f'<rect x="{x}" y="{y + s*0.5 - tw/2}" width="{s}" height="{tw}" fill="{tape}" opacity="0.9"/>')
    # Traits de vitesse, à gauche, de longueur décroissante vers le bas.
    lw = s * 0.11
    lx1 = x - s * 0.16
    for i, (dy, ln) in enumerate([(0.24, 0.42), (0.5, 0.30), (0.76, 0.18)]):
        yy = y + s * dy
        parts.append(f'<line x1="{lx1 - s*ln}" y1="{yy}" x2="{lx1}" y2="{yy}" stroke="{lines}" stroke-width="{lw}" stroke-linecap="round"/>')
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

def render(name, svg_markup, size):
    path = os.path.join(TMP, f'{name}.html')
    open(path, 'w').write(html(svg_markup, size))
    out = os.path.join(OUT, f'{name}.png')
    subprocess.run([SHELL, '--headless', '--no-sandbox', '--disable-gpu', '--hide-scrollbars',
                    '--default-background-color=00000000', '--force-device-scale-factor=1',
                    f'--window-size={size},{size}', f'--screenshot={out}', '--allow-file-access-from-files',
                    f'file://{os.path.abspath(path)}'], check=True, capture_output=True)
    with open(out, 'rb') as h:
        h.seek(16); w, hh = struct.unpack('>II', h.read(8))
    print(f'{out}: {w}x{hh}, {os.path.getsize(out)} o')

# Halo radial discret pour donner du relief au fond vert sans sortir de la charte.
GLOW = f'''<defs><radialGradient id="g" cx="0.35" cy="0.3" r="0.9">
  <stop offset="0" stop-color="#1A6A4E"/><stop offset="1" stop-color="{GREEN}"/></radialGradient></defs>
  <rect width="1024" height="1024" fill="url(#g)"/>'''

# 1. Icône iOS / générique : plein cadre (le système arrondit lui-même).
render('icon', svg(GLOW + motif(512, 512, 400)), 1024)
# 2. Adaptive icon Android : premier plan transparent, motif dans la zone sûre (66 % centraux).
render('android-icon-foreground', svg(motif(512, 512, 330)), 1024)
render('android-icon-background', svg(GLOW), 1024)
render('android-icon-monochrome', svg(motif(512, 512, 330, box=WHITE, lines=WHITE, cut=True)), 1024)
# 3. Splash : motif + marque, sur fond transparent (le vert vient de app.json).
wordmark = f'''<text x="512" y="700" text-anchor="middle" font-family="Sora" font-weight="700" font-size="96" fill="{WHITE}">BeninLink</text>
<text x="512" y="790" text-anchor="middle" font-family="Sora" font-weight="500" font-size="60" letter-spacing="6" fill="{OCRE}">LIVREUR</text>'''
render('splash-icon', svg(motif(512, 420, 300) + wordmark), 1024)
# 4. Favicon web : colis seul.
render('favicon', svg(f'<rect width="48" height="48" rx="10" fill="{GREEN}"/>' + motif(27, 24, 20).replace('1024', '48'), size=48), 48)
