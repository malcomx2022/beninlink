<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier 3, seconde moitié : FedaPay pour l'abonnement SaaS.
 *
 * La cartographie (bloc E) a établi que `subscriptions` n'a aucune colonne de
 * paiement : rien n'y relie un abonnement à sa transaction. L'idempotence du
 * webhook reste donc portée par `fedapay_transactions` (index unique sur
 * `provider_transaction_id`) ; on y ajoute seulement de quoi rejouer
 * `CompanyRepository::switchPlan()` à l'approbation : le plan et l'utilisateur
 * qui l'a demandé. `merchant_id` reste nul pour un abonnement — c'est la
 * société qui paie, pas un marchand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fedapay_transactions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('wallet_id')
                ->constrained('plans')->onUpdate('cascade')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('fedapay_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn('user_id');
        });
    }
};
