<?php

namespace App\Http\Requests\Merchantpayment;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankRequest extends FormRequest
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
        if(\Request::input('payment_method_name')):
            return [];
        else:
            return [
                'bank_name'   => ['required'],
                'holder_name' => ['required'],
                'account_no'  => ['required','numeric'],
                'branch_name' => ['required'],
                'routing_no'  => ['required','numeric'],
            ];
        endif;
    }
}
