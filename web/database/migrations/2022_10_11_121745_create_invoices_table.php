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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->string('invoice_id')->unique()->nullable();
            $table->string('invoice_date')->nullable();
            $table->decimal('total_charge',16,2)->nullable();
            $table->decimal('cash_collection',16,2)->nullable();
            $table->decimal('current_payable',16,2)->nullable();
            $table->longText('parcels_id')->nullable();
            $table->unsignedTinyInteger('status')->default(\App\Enums\InvoiceStatus::PROCESSING)->comment(
                ' Unpaid      = '.\App\Enums\InvoiceStatus::UNPAID.
                ', Processing  = '.\App\Enums\InvoiceStatus::PROCESSING.
                ', Paid        = '.\App\Enums\InvoiceStatus::PAID,
            );
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
        Schema::dropIfExists('invoices');
    }
};
