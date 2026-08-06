<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
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
            'company_name'        => ['required'],  
            'domain'              => 'required|unique:domains,domain_name|regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-]*$)/u',
            // owner user information
            'name'           => ['required','string','max:191'],
            'email'          => ['required','string','unique:users'],
            'password'       => ['required','string'],
            'mobile'         => ['required','numeric','unique:users'], 
            'address'        => ['string','max:191'] 
        ];
    }
}
