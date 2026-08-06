<?php

use App\Enums\Status;
use App\Enums\AccountType;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('type')->nullable()->comment(AccountType::ADMIN.'='.trans('AccountType.'.AccountType::ADMIN).', ' .AccountType::USER.'='.trans('AccountType.'.AccountType::USER));
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->tinyInteger('gateway')->nullable();
            $table->decimal('balance',16,2)->default(0);
            $table->string('account_holder_name')->nullable();
            $table->string('account_no')->nullable();
            $table->tinyInteger('bank')->nullable();
            $table->string('branch_name')->nullable();
            $table->decimal('opening_balance',16,2)->nullable();
            $table->string('mobile')->nullable();
            $table->tinyInteger('account_type')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.\App\Enums\Status::ACTIVE).', ' .\App\Enums\Status::INACTIVE.'='.trans('status.'.\App\Enums\Status::INACTIVE));
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
        Schema::dropIfExists('accounts');
    }
};
