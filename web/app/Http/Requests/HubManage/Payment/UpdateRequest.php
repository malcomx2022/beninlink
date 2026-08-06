<?php

namespace App\Http\Requests\HubManage\Payment;

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
                'hub_id'              =>['required','numeric'],
                'amount'              =>['required','numeric'],
                'transaction_id'      =>['required','string'],
                'from_account'        =>['required','numeric'],
                'description'         => ['required','string','max:500']
            ];
        }else{
            return [
                'hub_id'             =>['required','numeric'],
                'amount'             =>['required','numeric'],
                'description'        => ['required','string','max:500']
            ];
        }
    }
}
