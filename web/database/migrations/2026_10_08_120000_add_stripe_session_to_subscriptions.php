<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S129 — une session Stripe payée n'active qu'UN abonnement.
 *
 * Le retour `subscription/success` vérifiait la session (S1) mais ne retenait pas qu'elle avait déjà
 * servi : rappeler la même URL réactivait le plan pour une nouvelle période, sans nouveau paiement.
 * La colonne est unique : deux retours simultanés ne peuvent pas l'écrire tous les deux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->unique()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['stripe_session_id']);
            $table->dropColumn('stripe_session_id');
        });
    }
};
