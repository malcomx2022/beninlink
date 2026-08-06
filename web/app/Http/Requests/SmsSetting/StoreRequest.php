<?php

namespace App\Http\Requests\SmsSetting;

use App\Enums\SmsSetup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if(request()->smsMethod == SmsSetup::REVE){
            return [
                'reve_api_key'                => ['required','string','max:191'],
                'reve_secret_key'             => ['required','string','max:191'],
                'reve_api_url'                => ['required','string','max:1000'],
            ];
        }elseif(request()->smsMethod == SmsSetup::TWILIO) {
            return [
                'twilio_sid'                => ['required','string','max:191'],
                'twilio_token'             => ['required','string','max:191'],
                'twilio_from'                => ['required','string','max:1000'],
            ];
        }elseif(request()->smsMethod == SmsSetup::NEXMO) {
            return [
                'nexmo_key'                => ['required','string','max:1000'],
                'nexmo_secret_key'         => ['required','string','max:1000'],
            ];
        }else {
            return [];
        }

    }
}
