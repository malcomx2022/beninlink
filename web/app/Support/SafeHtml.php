<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Texte riche rendu tel quel, mais nettoyé (**S127**).
 *
 * Le socle rendait par `{!! … !!}` des textes écrits par d'autres que le lecteur : la description et
 * les réponses d'un ticket de support (écrites par un marchand, lues au back-office), les actualités
 * (écrites par le transporteur, lues par ses marchands), les pages, articles, services et FAQ du site
 * public. Le middleware `XSS` du socle exempte précisément `description`, et ne couvre pas l'API des
 * apps : un marchand glissait `<img src=x onerror=…>` dans un ticket et le script tournait dans la
 * session de l'agent qui l'ouvrait.
 *
 * HTMLPurifier (déjà présent, tiré par phpspreadsheet) garde la mise en forme d'un éditeur de texte et
 * retire scripts, gestionnaires d'évènements, styles et adresses `javascript:`.
 */
final class SafeHtml
{
    private static ?HTMLPurifier $purificateur = null;

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return self::purificateur()->purify($html);
    }

    private static function purificateur(): HTMLPurifier
    {
        if (self::$purificateur === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,ul,ol,li,blockquote,h1,h2,h3,h4,h5,h6,span,div,'
                . 'a[href|title],img[src|alt|width|height],table,thead,tbody,tr,th,td,hr,pre,code');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            self::$purificateur = new HTMLPurifier($config);
        }

        return self::$purificateur;
    }
}
