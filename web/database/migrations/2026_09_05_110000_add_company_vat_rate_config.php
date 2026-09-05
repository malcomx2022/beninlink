<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Décision métier 2026-09-05 — TVA au niveau de la société.
 * Chaque société reçoit `configs.vat_rate = 18` (Bénin) si elle n'en a pas ;
 * les marchands ayant déjà un taux propre (> 0) le conservent (VatRate::for).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (DB::table('general_settings')->pluck('id') as $companyId) {
            $exists = DB::table('configs')->where('company_id', $companyId)->where('key', 'vat_rate')->exists();
            if (!$exists) {
                DB::table('configs')->insert([
                    'company_id' => $companyId,
                    'key' => 'vat_rate',
                    'value' => 18,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('configs')->where('key', 'vat_rate')->delete();
    }
};
