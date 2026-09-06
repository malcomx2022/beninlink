<?php

namespace App\Http\Requests\DeliveryZone;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie des délais et de leur supplément (**D4**).
 *
 * Le supplément est **global** : il ne dépend pas de la zone. Le formulaire
 * n'offre donc pas de colonne par zone — c'est ce mélange-là que la refonte
 * défait.
 */
class DelaisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delays' => ['required', 'array'],
            'delays.*.id' => ['nullable', 'integer'],
            'delays.*.name' => ['nullable', 'string', 'max:191'],
            'delays.*.surcharge' => ['nullable', 'numeric', 'min:0'],
            'delays.*.status' => ['nullable', 'integer', 'between:0,1'],
            'delays.*.delete' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return ['delays.*.surcharge.min' => __('delivery_zone.surcharge_positive')];
    }
}
