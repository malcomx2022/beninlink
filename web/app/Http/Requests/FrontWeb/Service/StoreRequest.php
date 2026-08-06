<?php

namespace App\Http\Requests\FrontWeb\Service;

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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title'       => ['required'],
            'image'       => ['required','mimes:png,jpg'],
            'description' => ['required'],
            'position'    => ['numeric'], 
            'status'      => ['required','numeric']
        ];
    }
}
