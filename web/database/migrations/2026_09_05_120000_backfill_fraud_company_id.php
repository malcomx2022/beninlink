<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Décision métier 2026-09-05 — rattachement des fiches de fraude orphelines.
 * Avant S7, le panneau marchand créait les fiches sans `company_id` ; elles
 * sortaient de la liste noire de leur société. Rattachement par l'auteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('frauds')->whereNull('company_id')->whereNotNull('created_by')->get(['id', 'created_by']);
        foreach ($orphans as $fraud) {
            $companyId = DB::table('users')->where('id', $fraud->created_by)->value('company_id');
            if ($companyId !== null) {
                DB::table('frauds')->where('id', $fraud->id)->update(['company_id' => $companyId]);
            }
        }
    }

    public function down(): void
    {
        // Volontairement vide : un rattachement correct ne se défait pas.
    }
};
