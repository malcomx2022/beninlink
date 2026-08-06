<?php

use App\Enums\AccountHeads;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('user_type')->default(\App\Enums\UserType::ADMIN)->comment(\App\Enums\UserType::ADMIN.'='.trans('userType.'.\App\Enums\UserType::ADMIN).',' .\App\Enums\UserType::HUB.'='.trans('userType.'.\App\Enums\UserType::HUB))->nullable();
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->onDelete('cascade');
            $table->bigInteger('expense_id')->nullable();
            $table->foreignId('fund_transfer_id')->nullable()->constrained('fund_transfers')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->unsignedTinyInteger('type')->comment('income='.AccountHeads::INCOME.', expense='.AccountHeads::EXPENSE)->nullable();
            $table->decimal('amount',16,2)->nullable();
            $table->date('date')->nullable();
            $table->longText('note')->nullable();
            $table->string('cash_received_dvry')->nullable();
            $table->foreignId('income_id')->nullable()->constrained('incomes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_transactions');
    }
};
