<?php

namespace App\Http\Requests\Merchant;

use App\Models\User;
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
     * @return array
     */
    public function rules()
    {
        return [
            'name'                  => ['required','string','max:191'],
            'business_name'         => ['required','string'],
            'mobile'                => ['required','numeric','digits_between:11,14'], 
            'hub'                   => ['required','numeric'],
            'status'                => ['required','numeric'],
            'password'              => ['required','min:6'],
            'address'               => ['required','string','max:191'],
            'payment_period'        => ['numeric']

        ];
    }



    public function withValidator($validator)
    {

        $validator->after(function ($validator) { 
                if ($this->userUniqueCheck()) {
                    $validator->errors()->add('email',  'The email or phone has already been taken.');
                }
        });
    }

    private function userUniqueCheck()
    { 

        $queryArray['company_id']               = settings()->id; 
        $data = [];
        $data['email']   = $this->email;
        $data['mobile']  = $this->mobile;

        $user         = User::where($queryArray)->where(function($query)use($data){
            $query->where('email',$data['email']);
            $query->orWhere('mobile', $data['mobile']);
        })->first();
        if (blank($user)) {
            return false;
        }
        return true;
    }



}
