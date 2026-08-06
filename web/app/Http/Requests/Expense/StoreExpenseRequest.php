<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class StoreExpenseRequest extends FormRequest
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
        // $merchant     = ['required'];
        $delivery_man = ['required'];
        $title        = ['required'];
        $user         = '';

        if (Request::input('account_head') == "4") {
            $delivery_man = ['nullable'];
            $title        = ['nullable'];
        }
        else if (Request::input('account_head') == "5"){
            $delivery_man = ['required'];
            $title        = ['nullable'];
        }
        else if (Request::input('account_head') == "6"){
            $delivery_man = ['nullable'];
            $title        = ['required'];
            $user         = ['required'];
        };

        return [
            'account_head'      => ['required'],
            'delivery_man_id'   => $delivery_man,
            'title'             => $title,
            'user_id'           => $user,
            'account_id'        => ['required'],
            'amount'            => ['required','numeric'],
            'date'              => ['required'],
            'receipt'           => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
        ];
    }
}
