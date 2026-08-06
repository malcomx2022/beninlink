<?php

namespace App\Http\Requests\MerchantPanel\Parcel;

use Illuminate\Foundation\Http\FormRequest;

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
            'shop_id'           => ['required','numeric'],
            'category_id'       => ['required','numeric'],
            'delivery_type_id'  => ['required','numeric'],
            'customer_name'     => ['required','string','max:191'],
            'customer_address'  => ['required','string','max:191'],
            'customer_phone'    => ['required','string','max:191'],
        ];
    }
}
