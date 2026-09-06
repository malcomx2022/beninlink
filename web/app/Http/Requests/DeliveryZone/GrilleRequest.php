<?php

namespace App\Http\Requests\DeliveryZone;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Grille d'une catégorie : une ligne par tranche, une colonne par zone (**D4**).
 *
 * N'écrit que des lignes **zonées**. Les quatre colonnes héritées restent
 * intactes jusqu'à l'étape 6 : une société qui ne saisit rien continue de
 * facturer comme avant, et `DeliveryPricingBaselineTest` le vérifie.
 */
class GrilleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'integer'],
            'rows' => ['required', 'array'],
            'rows.*.weight' => ['nullable', 'integer', 'min:0'],
            'rows.*.delete' => ['nullable'],
            'rows.*.amounts' => ['nullable', 'array'],
            'rows.*.amounts.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return ['rows.*.amounts.*.min' => __('delivery_zone.amount_positive')];
    }
}
