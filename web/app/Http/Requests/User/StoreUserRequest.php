<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name'           => ['required','string','max:191'],
            'email'          => ['required','string','unique:users'],
            'password'       => ['required','string'],
            'mobile'         => ['required','numeric','digits_between:11,14','unique:users'],
            'nid_number'     => ['nullable','numeric','digits_between:1,20'],
            'designation_id' => ['required','numeric'],
            'department_id'  => ['required','numeric'],
            'image'          => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'joining_date'   => ['required'],
            'salary'         => ['numeric'],
            'address'        => ['required','string','max:191'],
            'status'         => ['required','numeric'],
        ];
    }

 

}
