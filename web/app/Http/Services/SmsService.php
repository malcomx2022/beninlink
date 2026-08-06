<?php


namespace App\Http\Services;

use App\Enums\Status;
use App\Models\Backend\SmsSetting;
use http\Client;
use Twilio\Rest\Client as TwilioClient;
class SmsService
{
    public function sendOtp($userPhone,$otpCode)
    {

        $smsSetting = smsSettings('reve_status');
        $smsTwilioSetting = smsSettings('twilio_status');
        if($smsSetting == Status::ACTIVE){
            $this->reveSms ('otp',$userPhone,$otpCode);
        }
        if($smsTwilioSetting == Status::ACTIVE){
            $this->twilioSms('otp',$userPhone,$otpCode);
        }

    }

    public function sendSms($userPhone,$msg)
    {

        $smsSetting       = smsSettings('reve_status');
        $smsTwilioSetting = smsSettings('twilio_status');
        $smsNexmoSetting  = smsSettings('nexmo_status');
        if($smsSetting == Status::ACTIVE){
            $this->reveSms ('sms',$userPhone,$msg);
        }
        if($smsTwilioSetting == Status::ACTIVE){
            $this->twilioSms('sms',$userPhone,$msg);
        }
        if($smsNexmoSetting == Status::ACTIVE){
            $this->nexmoSms('sms',$userPhone,$msg);
        }

    }

    private function reveSms ($type,$userPhone,$userMsg){
      
            try {
                    $api_key    = smsSettings('reve_api_key');
                    $api_secret = smsSettings('reve_secret_key');
                    $api_url    = smsSettings('reve_api_url');
                    $callerID   = settings()->name;
                    if($type == 'otp') {
                        $message = $userMsg . ' is your ' . settings()->name . ' verification code.';
                    }else {
                        $message = $userMsg;
                    }

                    $params = [
                        "apikey" => $api_key,
                        "secretkey" => $api_secret,
                        "callerID" => $callerID,
                        "toUser" => $userPhone,
                        "messageContent" => $message
                    ];

                    $url = $api_url . '?' . http_build_query($params);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 80); 
                    $response = curl_exec($ch);
                    curl_close($ch);  
                    return $response;
            } catch (\Exception $exception) {
                return $exception;
            }

    }

    private function twilioSms($type,$receiverNumber,$message){

        try {

            $account_sid = smsSettings('twilio_sid');
            $auth_token = smsSettings('twilio_token');
            $twilio_number = smsSettings('twilio_from');

            $client = new TwilioClient($account_sid, $auth_token); 
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);  
        return true;
        } catch (\Exception $exception) { 
            return $exception;
        }
    }

    private function nexmoSms($type,$receiverNumber,$message) {

        try {
            $nexmoKey = smsSettings('nexmo_key');
            $nexmoSecretKey = smsSettings('nexmo_secret_key');
            $basic  = new \Vonage\Client\Credentials\Basic($nexmoKey, $nexmoSecretKey);
            $client = new \Vonage\Client($basic);
            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($receiverNumber, settings()->name, $message)
            );
            $message = $response->current();

            if ($message->getStatus() == 0) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            return $e;
        }
    }


}
