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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('author')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('assetcategory_id')->nullable();
            $table->foreignId('hub_id')->nullable()->constrained('hubs');
            $table->string('supplyer_name')->nullable();
            $table->string('quantity')->nullable();
            $table->string('warranty')->nullable();
            $table->string('invoice_no')->nullable();
            $table->decimal('amount',13,2)->nullable();
            $table->longtext('description')->nullable();
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
        Schema::dropIfExists('assets');
    }
};
