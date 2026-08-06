<?php

namespace App\Http\Requests\Hub;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class UpdateHubRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name'    => 'required|string|max:191|unique:hubs,name,'.Request::input('id'),
            'phone'   => ['required','numeric','digits_between:11,14'],
            'address' => ['required','string','max:191'],
        ];
    }
}
