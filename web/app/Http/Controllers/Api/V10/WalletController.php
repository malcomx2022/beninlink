<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\WalletResource;
use App\Repositories\Wallet\WalletInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * Historique du porte-monnaie prépayé du marchand connecté.
 *
 * Comblait le dernier manque de l'écran `wallet` de mobile/ : le solde arrive
 * par `/profile` et la recharge par FedaPay, mais aucune route ne listait les
 * mouvements de `wallets`. Le repository filtre déjà par utilisateur pour un
 * marchand et pagine par 10 ; on l'expose tel quel, sans logique nouvelle.
 */
class WalletController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(private WalletInterface $repo)
    {
    }

    public function history(Request $request)
    {
        try {
            $page = $this->repo->get($request);
            return $this->responseWithPage(__('wallet.history'), ['entries' => WalletResource::collection($page)], $page);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('wallet.history'), [], 500);
        }
    }
}
