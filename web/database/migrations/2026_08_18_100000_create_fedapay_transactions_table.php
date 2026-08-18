<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des transactions FedaPay — chantier 3.
 *
 * Cette table porte l'**idempotence**, exigée par `.claude/rules/payments.md`.
 * La cartographie (bloc C) a établi que `WalletRepository::approved()` n'est
 * « ni transactionnelle ni idempotente » : deux appels créditent deux fois.
 * Le garde-fou vit donc ici, dans `provider_transaction_id` en **index unique** :
 * un webhook rejoué retrouve la ligne déjà marquée `approved` et n'agit pas.
 *
 * ⚠️ `company_id` est stocké explicitement : hors requête HTTP authentifiée,
 * `settings()` retombe sur la société 1 (bloc A). Un webhook, appelé par FedaPay
 * sans session, doit retrouver son locataire depuis la transaction — jamais
 * depuis `settings()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fedapay_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()
                ->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()
                ->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');

            /** Notre référence, générée avant l'appel : permet de retrouver l'opération. */
            $table->string('reference', 64)->unique();

            /** Identifiant FedaPay. Unique = clé d'idempotence du webhook. */
            $table->string('provider_transaction_id', 64)->nullable()->unique();

            /** Ce que finance le paiement : recharge de wallet ou abonnement SaaS. */
            $table->string('purpose', 32)->default('wallet_recharge');

            /** Montant en FCFA — entier, le franc CFA n'ayant pas de subdivision. */
            $table->unsignedBigInteger('amount');

            /** pending → approved | declined | canceled (miroir des événements FedaPay). */
            $table->string('status', 24)->default('pending');

            /** Ligne `wallets` créée à l'initiation, créditée à l'approbation. */
            $table->foreignId('wallet_id')->nullable();

            $table->string('customer_phone', 32)->nullable();
            $table->text('payment_url')->nullable();

            /** Charge utile du dernier webhook reçu — pour l'audit et le rapprochement. */
            $table->json('last_event')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fedapay_transactions');
    }
};
