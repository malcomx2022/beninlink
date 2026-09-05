<?php

namespace App\Exceptions;

use App\Models\Backend\Merchant;

/**
 * Le portefeuille du marchand ne couvre pas les frais du colis qu'on crée.
 *
 * Ce n'est pas une panne : c'est une règle métier, et le socle la porte déjà.
 * `Backend\ParcelController::store()` et `MerchantPanel\MerchantParcelController::store()`
 * refusaient tous deux la création quand `total_delivery_amount` dépassait
 * `wallet_balance`. Mais le contrôle vivait dans ces deux écrans seulement :
 * l'API mobile et les deux chemins de duplication créaient le colis et
 * débitaient un solde qui passait en négatif, sans plancher.
 *
 * Le contrôle est donc descendu là où le débit a lieu — `Services\Parcel\WalletDebit` —
 * et remonte sous cette forme pour que chaque appelant réponde dans sa langue :
 * un toast et une redirection vers la recharge côté web, un 422 côté API. Une
 * exception, et non un `false`, parce qu'un solde insuffisant n'est pas la même
 * chose qu'une panne : le marchand peut agir, il lui suffit de recharger.
 */
class InsufficientWalletBalance extends \Exception
{
    public function __construct(
        public readonly Merchant $merchant,
        public readonly float $required,
        public readonly float $available,
    ) {
        parent::__construct(sprintf(
            'Solde insuffisant pour le marchand %d : %s requis, %s disponible.',
            $merchant->id,
            $required,
            $available,
        ));
    }

    /** Ce qui manque pour que la création passe. Jamais négatif. */
    public function missing(): float
    {
        return max(0, $this->required - $this->available);
    }
}
