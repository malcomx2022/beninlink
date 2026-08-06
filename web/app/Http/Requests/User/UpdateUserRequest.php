<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class UpdateUserRequest extends FormRequest
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

        if (Request::input('id') != 1) {
            return [
                'name'           => ['required','string','max:191'],
                'email'          => 'required|string|unique:users,email,'.Request::input('id'),
                'password'       => ['nullable'],
                'mobile'         => 'required|numeric|digits_between:11,14|unique:users,mobile,'.Request::input('id'),
                'nid_number'     => ['nullable','numeric','digits_between:1,20'],

                'designation_id' => ['required','numeric'],
                'department_id'  => ['required','numeric'],
                'image'          => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
                'salary'         => ['numeric'],
                'joining_date'   => ['required'],
                'address'        => ['required','string','max:191'],
                'status'         => ['required','numeric'],
            ];
        }else{
            return [
                'name'           => ['required','string','max:191'],
                'email'          => 'required|string|unique:users,email,'.Request::input('id'),
                'password'       => ['nullable'],
                'mobile'         => 'required|numeric|digits_between:11,14|unique:users,mobile,'.Request::input('id'),
                'image'          => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
                'address'        => ['required','string','max:191'],
            ];
        }
    }
}
