<?php

namespace App\Http\Requests\FundTransfer;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'from_account'    => ['required_with:from_account','different:to_account'],
            'to_account'      => ['required'],
            'amount'          => ['required','numeric'],
            'date'            => ['required'],
        ];
    }
}
