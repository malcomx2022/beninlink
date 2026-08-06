<?php

namespace App\Http\Requests\Merchant;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Support\Facades\Request;

class SignUpRequest extends FormRequest
{
    use ApiReturnFormatTrait;
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
        // dd(Request::all());
        return [
            'business_name'     => ['required','string'],
            'full_name'         => ['required','string','max:191'],
            // 'first_name'        => ['required','string','max:191'],
            // 'last_name'         => ['nullable','string','max:191'],
            'address'           => ['required','string','max:191'],
            'mobile'            => ['required','numeric','digits_between:11,14' ],
            'password'          => ['required','min:6']
        ];
    }


    
    public function withValidator($validator)
    {

        $validator->after(function ($validator) { 
                if ($this->userUniqueCheck()) {
                    $validator->errors()->add('mobile',  'The  phone has already been taken.');
                }
        });
    }

    private function userUniqueCheck()
    { 

        $queryArray['company_id']               = settings()->id; 
        $data = []; 
        $data['mobile']  = $this->mobile;

        $user         = User::where($queryArray)->where(function($query)use($data){ 
            $query->where('mobile', $data['mobile']);
        })->first();
        if (blank($user)) {
            return false;
        }
        return true;
    }


    // public function failedValidation(Validator $validator)
    // {
    //     throw new HttpResponseException(response()->json([
    //         'success'   => false,
    //         'message'   => 'Validation errors',
    //         'data'      => $validator->errors()
    //     ], 422));
    // }
}
