<?php

use App\Enums\SupportStatus;
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
        Schema::create('supports', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->string('service')->nullable();
            $table->string('priority')->nullable();
            $table->string('subject')->nullable();
            $table->longtext('description')->nullable();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('attached_file')->nullable();
            $table->unsignedTinyInteger('status')->default(SupportStatus::PENDING)->comment(SupportStatus::PENDING.'= Pending,'.SupportStatus::PROCESSING.'= Processing,'.SupportStatus::RESOLVED.'= Resolved,'.SupportStatus::CLOSED.'= Closed');
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
        Schema::dropIfExists('supports');
    }
};
