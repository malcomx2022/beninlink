<?php

use App\Enums\AccountHeads;
use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Décision métier 2026-09-05 — chapitre des dépenses d'acquisition.
 * `account_heads` est une table globale (sans société) : une seule ligne
 * suffit. Les dépenses de la société plateforme saisies sous ce chapitre
 * alimentent le CAC (config/saas_reporting.php, mot-clé « acquisition »).
 */
return new class extends Migration
{
    private const NAME = 'Marketing et acquisition clients';

    public function up(): void
    {
        $exists = DB::table('account_heads')->where('type', AccountHeads::EXPENSE)->where('name', self::NAME)->exists();
        if (!$exists) {
            DB::table('account_heads')->insert([
                'type' => AccountHeads::EXPENSE,
                'name' => self::NAME,
                'status' => Status::ACTIVE,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('account_heads')->where('type', AccountHeads::EXPENSE)->where('name', self::NAME)->delete();
    }
};
