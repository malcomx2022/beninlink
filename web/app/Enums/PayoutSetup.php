<?php
namespace App\Enums;

interface PayoutSetup {
    const STRIPE       = 1;//
    CONST SSL_COMMERZ  = 2;//
    CONST PAYPAL       = 3;//
    CONST PAYONEER     = 4;
    CONST BKASH        = 5;//
    CONST VISA         = 6;
    CONST SKRILL       = 7;//
    CONST AAMARPAY     = 8;//
    CONST RAZORPAY     = 9;//
    CONST PAYSTACK     = 10;//
    CONST OFFLINE      = 11;//
    /**
     * FedaPay — Mobile Money Benin (MTN MoMo + Moov Money), chantier 3.
     *
     * F2 — la passerelle n'avait aucune place dans cette enumeration, donc
     * aucun ecran de reglages, et `gatewayEnabled()` ne pouvait pas la couper.
     * Son unique surface de configuration etait le `.env` du serveur.
     */
    CONST FEDAPAY      = 12;
}
