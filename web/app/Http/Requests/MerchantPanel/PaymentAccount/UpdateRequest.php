<?php

namespace App\Http\Requests\MerchantPanel\PaymentAccount;

use App\Enums\Merchant_panel\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class UpdateRequest extends FormRequest
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
        if(Request::input('payment_method') == PaymentMethod::bank){
            return [ 
                'payment_method'=>['required'],
                'bank_name'   => ['required'],
                'holder_name' => ['required'],
                'account_no'  => ['required','numeric'],
                'branch_name' => ['required'],
                'routing_no'  => ['required','numeric'],

            ];
        }elseif(Request::input('payment_method') == PaymentMethod::mobile){
            return [
                'payment_method'=>['required'],
                'mobile_holder_name'=>['required'],
                'mobile_company'   => ['required'],
                'mobile_no'        => ['required','numeric','digits_between:11,14'],
                'account_type'     => ['required'],
            ];
        }else{
            return [
                    'payment_method'         => ['required'],
            ];
        }
    }
}
