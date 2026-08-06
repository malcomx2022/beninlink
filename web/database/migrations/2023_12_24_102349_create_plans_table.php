<?php

use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->bigInteger('parcel_count')->default(0);
            $table->bigInteger('deliveryman_count')->default(0);  
            $table->bigInteger('days_count')->default(0);
            $table->decimal('price',22,2)->default(0);
            $table->longText('description')->nullable();  
            $table->bigInteger('position')->default(0);
            $table->longText('modules')->nullable(); 
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.' = Active, '.Status::INACTIVE.' = Inactive');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
