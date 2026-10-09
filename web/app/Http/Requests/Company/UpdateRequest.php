<?php

namespace App\Http\Requests\Company;

use Illuminate\Validation\Rules\Password;
use App\Rules\LegalIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class UpdateRequest extends FormRequest
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
            'currency'            => ['required'],
            'logo'                => ['mimes:png,jpg'],
            'plan_id'             => ['required'],
            'domain'              => 'required|regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-]*$)/u|unique:domains,domain_name,'.Request::input('domain_id'),
 
            // owner user information
            'name'           => ['required','string','max:191'],
            'email'          => 'required|string|unique:users,email,'.Request::input('id'), 
            'mobile'         => 'required|numeric|unique:users,mobile,'.Request::input('id'),
            'password'       => ['nullable', Password::defaults()], // S134 : écrit par update() s'il est saisi
            'nid_number'     => ['nullable','numeric' ],
            'designation_id' => ['required','numeric'],
            'department_id'  => ['required','numeric'],
            'image'          => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'joining_date'   => ['required'], 
            'address'        => ['required','string','max:191'],
            'status'         => ['required','numeric'],
            // Identifiants legaux du transporteur (chantier 2).
            // Format verifie, jamais exige : ils se completent dans les reglages.
            'ifu'   => ['nullable', new LegalIdentifier(LegalIdentifier::IFU)],
            'rccm'  => ['nullable', new LegalIdentifier(LegalIdentifier::RCCM)],
            'cnss'  => ['nullable', new LegalIdentifier(LegalIdentifier::CNSS)],
        ];

    }
}
