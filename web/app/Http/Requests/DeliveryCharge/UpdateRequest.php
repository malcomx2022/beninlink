<?php

namespace App\Http\Requests\DeliveryCharge;

use App\Models\Backend\DeliveryCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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

        if (Request::input('category') == 1) {
            return [
                'category'      => ['required'],
                'weight'        => ['required', 'numeric'],
                'same_day'      => ['required','numeric',],
                'next_day'      => ['required','numeric',],
                'sub_city'      => ['required','numeric',],
                'outside_city'  => ['required','numeric',],
                'position'      => ['required','numeric',],
                'status'        => ['required','numeric',],
            ];
        }
        else {
            return [
                'category'      => ['required', 'numeric'],
                'same_day'      => ['required','numeric',],
                'next_day'      => ['required','numeric',],
                'sub_city'      => ['required','numeric',],
                'outside_city'  => ['required','numeric',],
                'position'      => ['required','numeric',],
                'status'        => ['required','numeric',],
            ];
        }
    }


    
    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
                if ($this->userUniqueCheck()) {
                    $validator->errors()->add('weight', trans('validation.attributes.weight'));
                }
        });
    }

    private function userUniqueCheck()
    { 
        
        $id    = $this->id;
        $queryArray['company_id']               = settings()->id;
        $queryArray['category_id']              = $this->category;
        $queryArray['weight']                   = $this->weight;
        $deliverycharge                         = DeliveryCharge::where($queryArray)->where('id', '!=', $id)->first();
        if (blank($deliverycharge)) {
            return false;
        }
        return true;
    }

}
