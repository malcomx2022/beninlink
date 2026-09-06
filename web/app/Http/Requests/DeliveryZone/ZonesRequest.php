<?php

namespace App\Http\Requests\DeliveryZone;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Saisie de la liste des zones (**D4**) : une ligne par zone, ajout dynamique.
 *
 * Le `code` n'est pas validé ici parce qu'il n'est **jamais** repris d'une
 * ligne existante — le dépôt le dérive du libellé à la création, puis le fige.
 */
class ZonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zones' => ['required', 'array'],
            'zones.*.id' => ['nullable', 'integer'],
            'zones.*.name' => ['nullable', 'string', 'max:191'],
            'zones.*.status' => ['nullable', 'integer', 'between:0,1'],
            'zones.*.delete' => ['nullable'],
        ];
    }
}
