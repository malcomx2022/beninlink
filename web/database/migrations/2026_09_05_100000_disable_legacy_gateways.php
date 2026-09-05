<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S21 — désactive Aamarpay et SSLCommerz dans les réglages existants, pour
 * chaque société et chaque marchand. Les clés déjà saisies sont conservées ;
 * seul le statut passe à inactif. Voir config/payments.php.
 */
return new class extends Migration
{
    private const STATUS_KEYS = ['aamarpay_status', 'sslcommerz_status'];

    public function up(): void
    {
        DB::table('settings')->whereIn('key', self::STATUS_KEYS)->update(['value' => 0]);
        DB::table('merchant_settings')->whereIn('key', self::STATUS_KEYS)->update(['value' => 0]);
    }

    public function down(): void
    {
        // Volontairement vide : réactiver une passerelle est une décision, pas un rollback.
    }
};
