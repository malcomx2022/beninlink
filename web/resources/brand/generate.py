"""Génère les visuels par défaut du site web (**S111**) : logo, logo clair, favicon.

Ils s'affichent tant qu'une société n'a pas envoyé les siens (Réglages → Généraux) :
`GeneralSettings::getLogoImageAttribute()` et ses deux sœurs retombent sur
`public/images/default/`. Le socle y livrait le logo « WeCourier SAAS » de l'éditeur,
c'est-à-dire la marque d'un tiers sur la page publique, la connexion, l'inscription
et les relevés PDF d'un transporteur béninois.

Usage (depuis web/) : `python3 resources/brand/generate.py`. Il faut ImageMagick
(`convert`) et la police Sora, prise dans `../mobile/node_modules` comme le font les
deux apps (`mobile/assets/source/generate.py`). Régénérer plutôt que retoucher.
"""
import os
import subprocess

HERE = os.path.dirname(os.path.abspath(__file__))
WEB = os.path.dirname(os.path.dirname(HERE))
OUT = os.path.join(WEB, 'public', 'images', 'default')
SORA = os.path.join(os.path.dirname(WEB), 'mobile', 'node_modules', '@expo-google-fonts', 'sora', '700Bold', 'Sora_700Bold.ttf')
GREEN, OCRE, WHITE, CREAM = '#12503A', '#E0A63C', '#FFFFFF', '#FBF5E8'


def wordmark(path, benin_color):
    """« Benin » dans la couleur demandée, « Link » en ocre, sur fond transparent, 250 × 40."""
    subprocess.run([
        'convert', '-size', '250x40', 'xc:none', '-font', SORA, '-pointsize', '28',
        '-gravity', 'West',
        '-fill', benin_color, '-annotate', '+4+0', 'Benin',
        '-fill', OCRE, '-annotate', '+{}+0'.format(4 + largeur('Benin', 28)), 'Link',
        '-strip', path,
    ], check=True)


def largeur(texte, taille):
    sortie = subprocess.run(
        ['convert', '-font', SORA, '-pointsize', str(taille), 'label:' + texte, '-format', '%w', 'info:'],
        check=True, capture_output=True, text=True,
    )
    return int(sortie.stdout)


def favicon(path):
    """« BL » crème sur un carré vert arrondi, 80 × 80."""
    subprocess.run([
        'convert', '-size', '80x80', 'xc:none',
        '-fill', GREEN, '-draw', 'roundrectangle 0,0 79,79 16,16',
        '-font', SORA, '-pointsize', '34', '-gravity', 'Center',
        '-fill', CREAM, '-annotate', '-6+0', 'B', '-fill', OCRE, '-annotate', '+14+0', 'L',
        '-depth', '8', '-strip', path,
    ], check=True)


if __name__ == '__main__':
    if not os.path.exists(SORA):
        raise SystemExit('Police Sora absente : lancer `npm install` dans mobile/ (' + SORA + ')')
    wordmark(os.path.join(OUT, 'logo.png'), GREEN)
    wordmark(os.path.join(OUT, 'light-logo.png'), WHITE)
    favicon(os.path.join(OUT, 'favicon.png'))
    print('logo.png, light-logo.png, favicon.png écrits dans', OUT)
