<?php

namespace App\Http\Requests\Parcel;

use Illuminate\Foundation\Http\FormRequest;

/** Montant réellement encaissé, commun au web et aux deux routes livreur. */
class PartialDeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        return ['cash_collection' => ['required', 'integer', 'min:0']];
    }
}
