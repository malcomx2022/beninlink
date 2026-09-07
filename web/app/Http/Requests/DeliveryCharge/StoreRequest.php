<?php

namespace App\Http\Requests\DeliveryCharge;

use App\Models\Backend\DeliveryCharge;
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
        if (Request::input('category') == 1) {
            return [
                'category'      => ['required'],
                'weight'        => ['required','numeric'],
                // D4, étape 6 : une ligne = catégorie × zone × tranche.
                'zone'          => ['required','numeric',],
                'amount'        => ['required','numeric',],
                'position'      => ['required','numeric',],
                'status'        => ['required','numeric',],
            ];
        }
        else {
            return [
                'category'      => ['required', 'numeric'],
                // D4, étape 6 : une ligne = catégorie × zone × tranche.
                'zone'          => ['required','numeric',],
                'amount'        => ['required','numeric',],
                'position'      => ['required','numeric',],
                'status'        => ['required','numeric',],
            ];
        }

    }


    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
                if ($this->userUniqueCheck()) {
                    $validator->errors()->add('weight', trans('validation.attributes.weight'));
                }
        });
    }

    private function userUniqueCheck()
    { 

        $queryArray['company_id']               = settings()->id; 
        $queryArray['category_id']              = $this->category;
        // L'unicité porte désormais la zone : la même tranche existe dans
        // chacune, à des montants différents — c'est tout l'objet du modèle.
        $queryArray['zone_id']                  = $this->zone;
        $queryArray['weight']                   = $this->weight;
        $deliverycharge                         = DeliveryCharge::where($queryArray)->first();
        if (blank($deliverycharge)) {
            return false;
        }
        return true;
    }

}
