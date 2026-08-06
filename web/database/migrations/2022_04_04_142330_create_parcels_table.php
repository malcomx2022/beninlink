<?php

use App\Enums\BooleanStatus;
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
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->foreignId('merchant_shop_id')->nullable();
            $table->longtext('pickup_address')->nullable();
            $table->string('pickup_phone')->nullable();
            $table->string('pickup_lat')->nullable();
            $table->string('pickup_long')->nullable();
            $table->string('customer_lat')->nullable();
            $table->string('customer_long')->nullable();
            $table->string('priority_type_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->longtext('customer_address')->nullable();
            $table->string('invoice_no')->nullable();
            $table->unsignedTinyInteger('category_id')->nullable();
            $table->bigInteger('weight')->default(0)->nullable();
            $table->unsignedTinyInteger('delivery_type_id')->nullable();
            // $table->unsignedTinyInteger('status')->nullable();
            $table->bigInteger('packaging_id')->nullable();
            $table->unsignedBigInteger('first_hub_id')->nullable();//first hub id from hub
            $table->foreignId('hub_id')->nullable()->constrained('hubs');//hub id from hub
            $table->foreignId('transfer_hub_id')->nullable()->constrained('hubs');//hub id from hub
            $table->decimal('cash_collection',13,2)->nullable();
            $table->decimal('old_cash_collection',13,2)->nullable();
            $table->decimal('selling_price',13,2)->nullable();
            $table->decimal('liquid_fragile_amount',13,2)->nullable();
            $table->decimal('packaging_amount',13,2)->nullable();
            $table->decimal('delivery_charge',13,2)->nullable();
            $table->bigInteger('cod_charge')->nullable();
            $table->decimal('cod_amount',13,2)->nullable();
            $table->bigInteger('vat')->nullable();
            $table->decimal('vat_amount',13,2)->nullable();
            $table->decimal('total_delivery_amount',13,2)->nullable();
            $table->decimal('current_payable',13,2)->nullable();
            $table->string('tracking_id')->nullable();
            $table->longtext('note')->nullable();
            $table->unsignedTinyInteger('partial_delivered')->default(\App\Enums\BooleanStatus::NO)->comment('no=0,yes=1');
            $table->unsignedTinyInteger('status')->default(\App\Enums\ParcelStatus::PENDING)->comment(
                'pending             = 1,
                 picup_assign        = 2,
                 received_warehouse  = 3,
                 delivery_man_assign = 4,
                 deliver             = 5,
                 delivered           = 6,
                 return_warehouse    = 7,
                 assign_merchant     = 8,
                 returned_merchant   = 9'
            );
            $table->string('parcel_bank')->nullable();
            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->decimal('return_charges',13,2)->nullable()->comment('received by merchant return charges');
            $table->unsignedTinyInteger('return_to_courier')->default(BooleanStatus::NO)->comment('no=0,yes=1');
            $table->bigInteger('invoice_id')->nullable();
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
        Schema::dropIfExists('parcels');
    }
};
