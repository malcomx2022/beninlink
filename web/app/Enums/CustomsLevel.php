<?php
namespace App\Enums;

/**
 * Niveaux d'alerte douaniere — DAT §2.5.
 *
 * L'ordre croissant est significatif : quand plusieurs regles s'appliquent, la
 * plus elevee l'emporte. Ne pas renumeroter, les alertes deja emises portent la
 * valeur en base.
 */
Interface CustomsLevel{
    /** Document recommande — la livraison se fait sans. */
    const INFO          = 1;
    /** Document obligatoire — le colis part, mais risque un blocage en douane. */
    const WARNING       = 2;
    /** Livraison interdite sans le document — la creation du colis est refusee. */
    const BLOCKING      = 3;
}
