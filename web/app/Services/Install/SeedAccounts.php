<?php

namespace App\Services\Install;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * **S87** — les comptes d'amorçage du socle et leur mot de passe.
 *
 * Cinq semences de We Courier créent des comptes au mot de passe `12345678`,
 * écrit dans le code source que toutes les installations du socle partagent :
 * le super-administrateur, l'administrateur et l'agence de la société de
 * démonstration (`UserSeeder`), un marchand (`MerchantSeeder`) et un livreur
 * (`DeliveryManSeeder`). Le guide de mise en service demandait de « les changer
 * avant que le site soit joignable » : une consigne, que rien ne mesurait.
 *
 * Ici, en un seul endroit :
 *
 *  - la **liste** des comptes d'amorçage et le mot de passe **public** du socle ;
 *  - le mot de passe qu'une semence pose : le public en `local` et `testing`
 *    (la suite et le poste de développement ne changent pas), un mot de passe
 *    **tiré au sort** par compte partout ailleurs, affiché **une fois** dans la
 *    sortie de `db:seed` et conservé nulle part ;
 *  - ce que `beninlink:comptes-amorcage` vérifie avant que `deploy.sh` ne
 *    coupe le site : aucun de ces comptes ne porte plus le mot de passe public.
 */
final class SeedAccounts
{
    /** Le mot de passe du code source du socle. */
    public const MOT_DE_PASSE_PUBLIC = '12345678';

    /** Les comptes que les semences du socle créent, par leur courriel. */
    public const COMPTES = [
        'admin@wemaxdevs.com',       // super-administrateur (UserSeeder)
        'company@wemaxdevs.com',     // administrateur de la société de démonstration (UserSeeder)
        'branch@wemaxdevs.com',      // agence (UserSeeder)
        'merchant@wemaxdevs.com',    // marchand (MerchantSeeder)
        'deliveryman@wemaxit.com',   // livreur (DeliveryManSeeder)
    ];

    /** Les mots de passe tirés pendant cette exécution, par courriel. */
    private static array $tires = [];

    /** Le mot de passe public n'est acceptable que là où le site n'est pas joignable. */
    public static function publicAutorise(): bool
    {
        return app()->environment('local', 'testing');
    }

    /** Le mot de passe en clair à poser sur un compte d'amorçage. */
    public static function motDePasse(string $email): string
    {
        if (self::publicAutorise()) {
            return self::MOT_DE_PASSE_PUBLIC;
        }

        // Lettres et chiffres seulement : il se recopie depuis un terminal.
        return self::$tires[$email] ??= Str::password(20, true, true, false);
    }

    /**
     * Affiche le mot de passe tiré pour un compte — une seule fois, dans la
     * sortie de la semence. Rien à afficher quand c'est le public qui a été posé.
     */
    public static function annoncer(?Command $commande, string $email): void
    {
        if ($commande === null || ! isset(self::$tires[$email])) {
            return;
        }

        $commande->warn(sprintf(
            'Compte d\'amorçage %s — mot de passe tiré au sort, affiché une seule fois : %s',
            $email,
            self::$tires[$email]
        ));
    }

    /** @return array<string, string> courriel → mot de passe tiré pendant cette exécution */
    public static function tires(): array
    {
        return self::$tires;
    }

    public static function oublier(): void
    {
        self::$tires = [];
    }
}
