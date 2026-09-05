<?php

namespace App\Http\Requests\Parcel;

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
            'merchant_id'       => ['required','numeric'],
            'category_id'       => ['required','numeric'],
            'delivery_type_id'  => ['required','numeric'],
            'customer_name'     => ['required','string','max:191'],
            'customer_address'  => ['required','string','max:191'],
            'customer_phone'    => ['required','string','max:191'],

            /**
             * W3 — chantier 5, douane. Ces deux regles manquaient ICI, et
             * seulement ici : le panneau marchand et l'API les portaient depuis
             * le premier jour. `CustomsAllowed` dit pourtant dans son propre
             * commentaire qu'elle vit dans `StoreRequest` « parce que c'est le
             * seul point commun aux TROIS chemins de creation ». Le troisieme,
             * l'administration, ecrivait bel et bien `destination_country` et
             * `customs_category` depuis la requete (`ParcelRepository::store()`,
             * `duplicateStore()`, `update()`) sans jamais les valider : un colis
             * d'export couvert par une regle BLOQUANTE passait par le
             * back-office alors qu'il etait refuse partout ailleurs.
             *
             * Vide ou « BJ » : colis domestique, rien ne change. Sinon la
             * categorie devient obligatoire — sans elle aucune regle ne
             * s'applique et le blocage se contournerait en omettant le champ.
             */
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
