<?php

namespace App\Http\Requests\NewsOffer;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsOfferRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title'      => ['required'],
            'status'     => ['required'],
            'date'       => ['required'],
            'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
        ];
    }
}
