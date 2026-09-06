<?php

namespace App\Http\Requests\DeliveryZone;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Forfaits CEDEAO, pays par pays (**D4**).
 *
 * C'est l'écran qui manquait : la décision « la CEDEAO se facture au pays »
 * était prise, mais rien ne permettait d'entrer les montants. Tant qu'un pays
 * n'est pas saisi, `DeliveryChargeResolver::resolveByZone()` rend `null` — il
 * ne facture pas au hasard.
 */
class PaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'countries' => ['required', 'array'],
            'countries.*.id' => ['nullable', 'integer'],
            'countries.*.code' => ['nullable', 'string', 'size:2'],
            'countries.*.name' => ['nullable', 'string', 'max:191'],
            'countries.*.flat_amount' => ['nullable', 'numeric', 'min:0'],
            'countries.*.status' => ['nullable', 'integer', 'between:0,1'],
            'countries.*.delete' => ['nullable'],
        ];
    }
}
