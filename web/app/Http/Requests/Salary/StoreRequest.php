<?php

namespace App\Http\Requests\Salary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

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
     * @return array<string, mixed>
     */
    public function rules()
    {

        return [
            'user_id'   => ['required'],
            'account_id'=> ['required'],
            'month'     => ['required'],
            'date'      => ['required'],
            'amount'    => ['required','numeric'],

        ];
    }
}
