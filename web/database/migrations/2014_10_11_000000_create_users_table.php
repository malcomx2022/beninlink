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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unique_id')->unique()->nullable();
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('company_owner')->default(BooleanStatus::NO)->comment(BooleanStatus::YES.'= Yes, '.BooleanStatus::NO.'= No');
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');//general settings means company
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('mobile')->nullable();
            $table->string('nid_number')->nullable();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->onDelete('cascade');
            $table->unsignedTinyInteger('user_type')->default(\App\Enums\UserType::ADMIN)->comment(\App\Enums\UserType::ADMIN.'='.trans('userType.'.\App\Enums\UserType::ADMIN).', ' .\App\Enums\UserType::MERCHANT.'='.trans('userType.'.\App\Enums\UserType::MERCHANT).', ' .\App\Enums\UserType::DELIVERYMAN.'='.trans('userType.'.\App\Enums\UserType::DELIVERYMAN).', ' .\App\Enums\UserType::INCHARGE.'='.trans('userType.'.\App\Enums\UserType::INCHARGE))->nullable();
            $table->foreignId('image_id')->nullable()->constrained('uploads')->onUpdate('cascade')->onDelete('cascade');
            $table->string('joining_date')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->longText('permissions')->nullable();  
            $table->integer('otp')->nullable();
            $table->decimal('salary',16,2)->default(0);
            $table->string('device_token')->nullable();
            $table->string('web_token')->nullable();
            $table->unsignedTinyInteger('status')->default(\App\Enums\Status::ACTIVE)->comment(\App\Enums\Status::ACTIVE.'='.trans('status.'.\App\Enums\Status::ACTIVE).', ' .\App\Enums\Status::INACTIVE.'='.trans('status.'.\App\Enums\Status::INACTIVE));
            $table->unsignedTinyInteger('verification_status')->default(\App\Enums\Status::ACTIVE)->comment(\App\Enums\Status::ACTIVE.'='.trans('status.'.\App\Enums\Status::ACTIVE).', ' .\App\Enums\Status::INACTIVE.'='.trans('status.'.\App\Enums\Status::INACTIVE));
            $table->string('google_id')->unique()->nullable();
            $table->string('facebook_id')->unique()->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
