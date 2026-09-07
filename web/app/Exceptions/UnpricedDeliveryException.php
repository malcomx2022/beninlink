<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Une route (zone × tranche, ou pays d'export) n'a pas de tarif (**D4**).
 *
 * `resolveByZone()` rend `null` dans ce cas plutôt que d'emprunter un montant
 * voisin. Le calcul refuse alors d'aller plus loin : le colis n'est pas créé,
 * et personne ne découvre le prix sur la facture du marchand.
 *
 * C'est un **filet**, pas l'expérience utilisateur : la règle de validation
 * `DeliveryRoutePriced` attrape le cas dans le formulaire, avec un message sur
 * le bon champ. Cette exception protège les chemins qui contourneraient la
 * validation — c'est la leçon de S2, où le prix venait du navigateur.
 */
class UnpricedDeliveryException extends RuntimeException
{
    /**
     * Aucune zone sur le colis (**D4, étape 6**).
     *
     * Depuis le retrait des quatre colonnes, il n'existe plus qu'un seul axe
     * de tarification. Un colis sans zone n'est donc pas « à l'ancien tarif » :
     * il n'a pas de tarif du tout, et le dire vaut mieux que rendre zéro.
     */
    public static function sansZone(): self
    {
        return new self("Aucune zone de livraison n'est indiquée : le tarif ne peut pas être déterminé.");
    }

    public static function pourZone(int $zoneId, ?string $pays = null): self
    {
        return new self($pays === null
            ? "Aucun tarif n'est défini pour la zone {$zoneId}."
            : "Aucun forfait n'est défini pour le pays {$pays} dans la zone {$zoneId}.");
    }
}
