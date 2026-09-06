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

            /**
             * D4, etape 5 bis — la route du colis : zone et delai.
             *
             * Facultatifs : sans zone, le colis suit le bareme herite et le
             * `delivery_type_id` ci-dessus, exactement comme avant. Avec une
             * zone, `DeliveryRoutePriced` verifie que la route EST tarifee —
             * sinon `ChargeCalculator` leverait, et le marchand verrait une
             * erreur serveur au lieu d'un message sur le bon champ.
             */
            'zone_id'  => ['nullable','numeric', new \App\Rules\DeliveryRoutePriced()],
            'delay_id' => ['nullable','numeric'],
            'destination_country' => ['nullable','string','size:2'],
            'customs_category'    => [
                // Exigee pour un EXPORT seulement : `required_with` aurait aussi
                // reclame une categorie a un colis explicitement marque « BJ ».
                \Illuminate\Validation\Rule::requiredIf(
                    fn () => app(\App\Services\Customs\CustomsService::class)->isExport(request('destination_country'))
                ),
                'nullable','string','max:32', new \App\Rules\CustomsAllowed(),
            ],
        ];
    }
}
