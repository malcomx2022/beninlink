<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Traçabilité + idempotence des transactions FedaPay (journal d'audit). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fedapay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('amount');        // FCFA entier
            $table->string('purpose');                   // wallet_recharge | saas_subscription
            $table->string('status')->default('pending');// pending | paid | failed
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index('provider_reference');
        });
    }
    public function down(): void { Schema::dropIfExists('fedapay_transactions'); }
};
