<?php

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
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onDelete('cascade');
            $table->string('payment_method')->nullable();
            //bank info
            $table->string('bank_name')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('routing_no')->nullable();
            //mobile info
            $table->string('mobile_company')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('account_type')->nullable();
            $table->unsignedTinyInteger('status')->default(\App\Enums\Status::ACTIVE)->comment(\App\Enums\Status::ACTIVE.'='.trans('status.'.\App\Enums\Status::ACTIVE).', ' .\App\Enums\Status::INACTIVE.'='.trans('status.'.\App\Enums\Status::INACTIVE));
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
        Schema::dropIfExists('payment_accounts');
    }
};
