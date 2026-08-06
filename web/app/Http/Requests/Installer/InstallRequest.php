<?php

namespace App\Http\Requests\Installer;

use Illuminate\Foundation\Http\FormRequest;

class InstallRequest extends FormRequest
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
            'host'         => 'required',
            'dbuser'       => 'required',
            'dbname'       => 'required',
            'first_name'   => 'required',
            'last_name'    => 'required',
            'email'        => 'required|email',
            'password'     => 'required',
            'purchase_code'=> ['required']
        ];
    }


}
