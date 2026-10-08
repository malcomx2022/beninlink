<?php

namespace App\Support;

/**
 * **S133** — un numéro béninois se saisit comme on le dit, il se range sous une seule forme.
 *
 * Le socle exige 11 à 14 chiffres sans espace ni « + » (format du Bangladesh) : une PME qui
 * tape « 01 97 00 00 00 » ou « +229 01 97 00 00 00 » est refusée. Depuis la migration ARCEP
 * du 30 novembre 2024, un numéro béninois a 10 chiffres et commence par 01. La forme rangée
 * est l'international sans « + » : `2290197000000` (13 chiffres) — celle que les écrans
 * demandaient déjà et que les passerelles SMS composent.
 *
 * Ce qui se lit comme un numéro béninois devient cette forme :
 * - national à 10 chiffres `01XXXXXXXX` → `229` devant ;
 * - ancien national à 8 chiffres et ancien international `229XXXXXXXX` → le `01` de la migration ;
 * - séparateurs (espaces, points, tirets, parenthèses) et préfixe `+` ou `00` retirés.
 * Un autre numéro garde ses chiffres ; une valeur qui n'est pas un numéro ressort telle quelle,
 * pour que la validation la refuse avec son message.
 */
final class BeninPhone
{
    public static function normalize(mixed $valeur): mixed
    {
        if (! is_string($valeur) || ! preg_match('/^\s*(\+|00)?[\d\s.\-()]+$/', $valeur)) {
            return $valeur;
        }

        $chiffres = preg_replace('/\D/', '', preg_replace('/^\s*(\+|00)/', '', $valeur));
        if ($chiffres === '') {
            return $valeur;
        }

        return match (true) {
            strlen($chiffres) === 8                                     => '22901' . $chiffres,
            strlen($chiffres) === 10 && str_starts_with($chiffres, '01') => '229' . $chiffres,
            strlen($chiffres) === 11 && str_starts_with($chiffres, '229') => '22901' . substr($chiffres, 3),
            default                                                     => $chiffres,
        };
    }
}
