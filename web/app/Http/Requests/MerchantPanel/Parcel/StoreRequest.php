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

            /**
             * Chantier 5 — douane. Vide ou « BJ » : colis domestique, rien ne
             * change. Sinon la categorie devient obligatoire (sans elle, aucune
             * regle ne s'applique et le blocage se contournerait en omettant le
             * champ) et CustomsAllowed refuse les couples pays/categorie
             * interdits.
             */
            'destination_country' => ['nullable','string','size:2'],
            'customs_category'    => ['required_with:destination_country','nullable','string','max:32', new \App\Rules\CustomsAllowed()],
        ];
    }
}
