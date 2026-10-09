<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S137 — un code SMS a une échéance. `users.otp` n'en avait pas : un code restait valable sans
 * limite de durée et, par l'API, ouvrait une session à quiconque le présentait avec le numéro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'otp_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'otp_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('otp_expires_at');
            });
        }
    }
};
