<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [ 
            'name'          => ['required'],  
            'parcel_count'  => ['required','numeric'],
            'days_count'    => ['required','numeric'],
            'price'         => ['required','numeric','gt:0.49'], 
            'position'      => ['numeric'],
            'status'        => ['required','numeric']
        ];
    }
}
