<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // FedaPay appelle ce webhook de serveur à serveur : aucun jeton CSRF
        // n'existe dans ce contexte. La protection vient de la signature
        // X-FEDAPAY-SIGNATURE, vérifiée avant tout traitement.
        'fedapay/webhook',
        '/success',
        '/cancel',
        '/fail',
        '/ipn',
        '/pay-via-ajax', // only required to run example codes. Please see bellow.

        //admin
        '/admin/payout/success',
        '/admin/payout/cancel',
        '/admin/payout/fail',
        '/admin/payout/ipn',
        '/admin/payout/pay-via-ajax', // only required to run example codes. Please see bellow.

        //aamarpay
        '/aamarpay-success',
        '/aamarpay-fail',

        '/subscription/success',
        '/subscription/cancel',
    ];
}
