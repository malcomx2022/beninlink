<?php

use App\Enums\AccountHeads;
use App\Enums\SmsSendStatus;
use App\Enums\Status;
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
        Schema::create('sms_send_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('sms_send_status')->comment(SmsSendStatus::PARCEL_CREATE.'='.trans('SmsSendStatus.'.SmsSendStatus::PARCEL_CREATE).', '.SmsSendStatus::DELIVERED_CANCEL_CUSTOMER.'='.trans('SmsSendStatus.'.SmsSendStatus::DELIVERED_CANCEL_CUSTOMER).', ' .SmsSendStatus::DELIVERED_CANCEL_MERCHANT.'='.trans('SmsSendStatus.'.SmsSendStatus::DELIVERED_CANCEL_MERCHANT));
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', ' .Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
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
        Schema::dropIfExists('sms_settings');
    }
};
