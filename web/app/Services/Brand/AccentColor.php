<?php

namespace App\Services\Brand;

/**
 * Lot 3 de la charte web — l'ocre devient réglable par transporteur.
 *
 * ┌─ POURQUOI CETTE CLASSE ────────────────────────────────────────────────────┐
 * │ `tokens.css` ne déclare pas UNE couleur d'accent mais QUATRE jetons, dont   │
 * │ trois sont des contrastes MESURÉS contre le quatrième :                     │
 * │                                                                            │
 * │   --bl-accent       l'ocre lui-même                                        │
 * │   --bl-accent-dark  sa variante de survol                                  │
 * │   --bl-on-accent    l'encre POSÉE DESSUS ......... 6,40:1 sur l'ocre       │
 * │   --bl-accent-text  l'ocre EN TEXTE sur blanc .... 5,93:1                  │
 * │                                                                            │
 * │ Rendre le premier réglable sans recalculer les trois autres, c'est livrer   │
 * │ un transporteur au jaune vif avec une encre brune illisible dessus. Le      │
 * │ lot 1 a écrit que ces valeurs sont « mesurées, pas choisies à l'œil » : on  │
 * │ ne peut donc pas les laisser fixes quand leur référence bouge.              │
 * └────────────────────────────────────────────────────────────────────────────┘
 *
 * La classe ne connaît pas la base : elle prend une couleur, elle rend des
 * jetons. C'est `resources/views/beninlink/brand-accent.blade.php` qui lit le
 * réglage du transporteur, et `GeneralSettingsRepository` qui l'écrit.
 *
 * ⚠️ **La charte garde le dernier mot sur elle-même.** Quand le transporteur est
 * resté sur l'ocre, `jetons()` rend un tableau VIDE : rien n'est injecté, et
 * `tokens.css` continue de servir ses quatre valeurs mesurées à la main — qui
 * sont meilleures que ce qu'un calcul produit (l'encre `#3A2A06` est un brun
 * chaud, pas un noir). Le calcul ne sert qu'aux couleurs que personne n'a
 * mesurées, c'est-à-dire à toutes les autres.
 *
 * ⚠️ Les jetons de PASTILLE (`--bl-pill-*`) ne bougent pas avec l'accent : ce
 * sont des SÉMANTIQUES de statut, partagées avec `mobile/`, pas la marque du
 * transporteur. Un colis en transit est ocre chez tout le monde.
 */
final class AccentColor
{
    /** Ocre de la charte — `mobile/src/theme/colors.ts` (`accent`). */
    public const CHARTE = '#E0A63C';

    /**
     * Les trois jetons dérivés, tels que `tokens.css` les porte. Ils ne sont
     * PAS recalculés : ils ont été mesurés au lot 1. Ils figurent ici pour que
     * la classe sache reconnaître « le transporteur est resté sur la charte »
     * et pour que `WebAccentColorTest` les compare, jeton par jeton, à
     * `tokens.css` — une retouche d'un côté sans l'autre fait échouer la suite.
     */
    public const CHARTE_DARK = '#C08D2A';
    public const CHARTE_ENCRE = '#3A2A06';
    public const CHARTE_TEXTE = '#8A5A00';

    /** WCAG 2.1 AA, petit corps. Une pastille ou un libellé de bouton en est. */
    private const CIBLE = 4.5;

    /** `--bl-text` : l'encre douce de la charte, préférée au noir pur. */
    private const ENCRE_DOUCE = '#1A1A1A';

    /** `--bl-surface` : le fond sur lequel l'accent sert de TEXTE. */
    private const SURFACE = '#FFFFFF';

    /**
     * `#abc`, `#AABBCC`, avec ou sans dièse → `#AABBCC`. Tout le reste → `null`.
     *
     * Ce filtre n'est pas décoratif : la valeur retournée finit dans un bloc
     * `<style>` du site public. Blade échappe `<` et `>`, donc on ne sort pas
     * de l'élément — mais `;`, `{` et `}` passent, et suffisent à injecter du
     * CSS. Ici, c'est un hexadécimal ou rien.
     */
    public static function normalise(?string $hex): ?string
    {
        if ($hex === null) {
            return null;
        }

        $hex = strtoupper(trim($hex));
        $hex = ltrim($hex, '#');

        if (preg_match('/^[0-9A-F]{3}$/', $hex) === 1) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return preg_match('/^[0-9A-F]{6}$/', $hex) === 1 ? '#' . $hex : null;
    }

    /**
     * Les jetons à injecter pour ce transporteur.
     *
     * Rend `[]` — donc AUCUN octet ajouté à la page — dans les deux cas où la
     * charte suffit : la couleur est celle de la charte, ou elle est absente /
     * illisible (colonne à `null` sur une base d'avant la migration, valeur
     * saisie hors de l'écran des paramètres). Le repli, c'est `tokens.css`.
     *
     * @return array<string,string> nom de jeton CSS → hexadécimal `#RRGGBB`
     */
    public static function jetons(?string $accent): array
    {
        $accent = self::normalise($accent);

        if ($accent === null || $accent === self::CHARTE) {
            return [];
        }

        return [
            '--bl-accent' => $accent,
            '--bl-accent-dark' => self::sombre($accent),
            '--bl-on-accent' => self::encre($accent),
            '--bl-accent-text' => self::texte($accent),
        ];
    }

    /**
     * L'encre à poser SUR l'accent (`--bl-on-accent`).
     *
     * On essaie d'abord les deux couleurs de la charte — l'encre douce et le
     * blanc — et on garde la meilleure. Si aucune des deux n'atteint AA, on
     * retombe sur le noir ou le blanc PURS, qui eux l'atteignent toujours : la
     * couleur la plus défavorable qui soit (celle dont les contrastes au noir
     * et au blanc s'égalisent) y tient encore 4,58:1, et le pire cas réellement
     * rencontré sur un balayage de 140 608 couleurs est 4,67:1.
     *
     * Autrement dit, cette méthode ne peut pas rendre un texte illisible,
     * quelle que soit la couleur choisie par le transporteur.
     * `WebAccentColorTest` le vérifie en BALAYANT les couleurs et en recalculant
     * les contrastes — pas en croyant ce commentaire.
     */
    public static function encre(string $accent): string
    {
        $accent = self::normalise($accent) ?? self::CHARTE;

        $douce = self::contraste(self::ENCRE_DOUCE, $accent);
        $blanc = self::contraste(self::SURFACE, $accent);

        if (max($douce, $blanc) >= self::CIBLE) {
            return $douce >= $blanc ? self::ENCRE_DOUCE : self::SURFACE;
        }

        return self::contraste('#000000', $accent) >= self::contraste('#FFFFFF', $accent)
            ? '#000000'
            : '#FFFFFF';
    }

    /**
     * L'accent EN TEXTE sur fond blanc (`--bl-accent-text`).
     *
     * Un accent est vif par nature : posé en texte sur du blanc, il échoue
     * presque toujours (l'ocre de la charte : 2,17:1). On l'assombrit donc par
     * pas de 8 % jusqu'à ce qu'il tienne AA. L'assombrissement garde la teinte
     * — c'est ce que la charte fait déjà à la main pour l'ocre (`#8A5A00`) et
     * pour le vert de succès. La boucle termine toujours : chaque pas fait
     * strictement décroître les canaux non nuls, et le noir tient 21:1.
     */
    public static function texte(string $accent): string
    {
        [$r, $v, $b] = self::canaux(self::normalise($accent) ?? self::CHARTE);

        // 60 pas suffisent largement pour amener 255 à 0 par pas de 8 % ; la
        // borne n'est là que pour qu'aucune erreur d'arrondi ne boucle sans fin.
        for ($pas = 0; $pas < 60; $pas++) {
            $couleur = self::hex($r, $v, $b);

            if (self::contraste($couleur, self::SURFACE) >= self::CIBLE) {
                return $couleur;
            }

            $r = (int) floor($r * 0.92);
            $v = (int) floor($v * 0.92);
            $b = (int) floor($b * 0.92);
        }

        return '#000000';
    }

    /**
     * La variante de survol (`--bl-accent-dark`).
     *
     * Un simple assombrissement de 14 %, la proportion que la charte applique
     * elle-même entre `--bl-accent` et `--bl-accent-dark`. Ce jeton ne porte
     * aucun texte à lui seul : il prend l'encre de l'accent, donc il n'a pas de
     * contrainte de contraste propre.
     */
    public static function sombre(string $accent): string
    {
        [$r, $v, $b] = self::canaux(self::normalise($accent) ?? self::CHARTE);

        return self::hex(
            (int) round($r * 0.86),
            (int) round($v * 0.86),
            (int) round($b * 0.86)
        );
    }

    /** Contraste WCAG 2.1 entre deux couleurs `#RRGGBB`. */
    public static function contraste(string $a, string $b): float
    {
        $x = self::luminance($a);
        $y = self::luminance($b);

        return $x > $y ? ($x + 0.05) / ($y + 0.05) : ($y + 0.05) / ($x + 0.05);
    }

    /** @return array{int,int,int} */
    private static function canaux(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function hex(int $r, int $v, int $b): string
    {
        return sprintf('#%02X%02X%02X', max(0, min(255, $r)), max(0, min(255, $v)), max(0, min(255, $b)));
    }

    /** Luminance relative WCAG 2.1. */
    private static function luminance(string $hex): float
    {
        [$r, $v, $b] = self::canaux($hex);

        $canal = static function (int $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $canal($r) + 0.7152 * $canal($v) + 0.0722 * $canal($b);
    }
}
