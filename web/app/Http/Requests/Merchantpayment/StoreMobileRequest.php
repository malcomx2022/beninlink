<?php

namespace App\Http\Requests\Merchantpayment;

use Illuminate\Foundation\Http\FormRequest;

class StoreMobileRequest extends FormRequest
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

            'mobile_holder_name'=>['required'],
            'mobile_company'   => ['required'],
            'mobile_no'        => ['required','numeric','digits_between:11,14'],
            'account_type'     => ['required'],
        ];
    }
}
