<?php

namespace App\Http\Requests\NotificationSetting;

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
        return [
            'fcm_secret_key'                => ['required','string','max:800'],
            'fcm_topic'                => ['required','string','max:20'],
        ];
    }
}
