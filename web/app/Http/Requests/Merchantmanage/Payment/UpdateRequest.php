<?php

namespace App\Http\Requests\Merchantmanage\Payment;

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
        if(Request::input('isprocess')){
            return [
                'merchant'        =>['required'],
                'amount'          =>['required','numeric'],
                'merchant_account' =>['required'],
                'transaction_id'  =>['required'],
                'from_account'    =>['required']
            ];
        }else{

            return [
                'merchant'       =>['required'],
                'amount'         =>['required','numeric'],
                'merchant_account'=>['required']
            ];
        }
    }
}
