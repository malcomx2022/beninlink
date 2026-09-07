<?php

namespace App\Http\Requests\MerchantDeliveryCharge;

use App\Enums\Status;
use App\Models\Backend\MerchantDeliveryCharge;
use Illuminate\Foundation\Http\FormRequest;

class MerchantDeliveryChargeRequest extends FormRequest
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
            'delivery_charge_id' => ['required', 'numeric'],
            // D4, étape 6 : un montant par zone, plus quatre colonnes.
            'amount'             => ['required','numeric'],
            'status'             => ['required','numeric'],
        ];
    }

    public function attributes()
    {
        return [
            'delivery_charge_id'         => trans('validation.attributes.delivery_category'),
            'status'                     => trans('validation.attributes.status'),
            'amount'                     => trans('delivery_zone.amount'),
        ];
    }

    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
                if ($this->userUniqueCheck()) {
                    $validator->errors()->add('delivery_charge_id', trans('validation.attributes.delivery_category'));
                }
        });
    }

    private function userUniqueCheck()
    {
        $id                                    = $this->id;
        $queryArray['delivery_charge_id']      = request('delivery_charge_id');
        $queryArray['merchant_id']             = $this->merchant;
        $hubInCharge                           = MerchantDeliveryCharge::where($queryArray)->where('id', '!=', $id)->first();

        if (blank($hubInCharge)) {
            return false;
        }
        return true;
    }
}
