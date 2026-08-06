<?php

namespace App\Http\Requests\Account;

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
// dd(Request::all());
        if (Request::input('gateway') == 1) {
            return [
                'type'                => ['required'],
                'user'                => ['required'],
                'gateway'             => ['required'],
                'balance'             => ['required','numeric'],
            ];
        }
        else if (Request::input('gateway') == 2) {
            return [
                'type'                => ['required'],
                'user'                => ['required'],
                'gateway'             => ['required'],
                'account_holder_name' => ['required'],
                'account_no'          => ['required','numeric'],
                'bank'                => ['required'],
                'branch_name'         => ['required'],
                'opening_balance'     => ['required','numeric'],
            ];
        }
        else if (Request::input('gateway') == 3 || Request::input('gateway') == 4 || Request::input('gateway') == 5) {
            return [
                'type'                => ['required'],
                'user'                => ['required'],
                'gateway'             => ['required'],
                'account_holder_name' => ['required'],
                'mobile'              => ['required','numeric','digits_between:11,14'],
                'account_type'        => ['required'],
                'opening_balance'     => ['required','numeric'],
                'status'              => ['required'],
            ];
        }else{
            return [
                'type'                => ['required'],
                'user'                => ['required'],
                'gateway'             => ['required'],
            ];
        }
    }
}
