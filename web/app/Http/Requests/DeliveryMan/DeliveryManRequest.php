<?php

namespace App\Http\Requests\DeliveryMan;

use App\Models\Backend\DeliveryMan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class DeliveryManRequest extends FormRequest
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
        if (Request::input('id')) {

            $user   = DeliveryMan::findOrFail(Request::input('id'));
            $userID = $user->user_id;

            $email    = ['required', 'email', 'string', Rule::unique("users", "email")->ignore($userID)];
            $mobile   = ['required', 'numeric','digits_between:11,14', Rule::unique("users", "mobile")->ignore($userID)];
            $password = ['nullable'];
        } else {
            $email    = ['required', 'email', 'string', 'unique:users,email'];
            $mobile   = ['required', 'numeric','digits_between:11,14', 'unique:users,mobile'];
            $password = ['required', 'min:6'];
        }

        return [
            'name'                       => ['required', 'string','max:191'],
            'email'                      => $email,
            'password'                   => $password,
            'mobile'                     => $mobile,
            'address'                    => ['required','string', 'max:200'],
            'hub_id'                     => ['required', 'numeric'],
            'delivery_charge'            => ['nullable', 'numeric'],
            'pickup_charge'              => ['nullable', 'numeric'],
            'return_charge'              => ['nullable', 'numeric'],
            'opening_balance'            => ['nullable', 'numeric'],
            'status'                     => ['required', 'numeric'],
            'image_id'                   => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'driving_license_image_id'   => 'nullable|image|mimes:jpeg,png,jpg|max:5098',
            'salary'                     => ['numeric']

        ];
    }

    public function attributes()
    {
        return [
            'name'                       => trans('validation.attributes.name'),
            'status'                     => trans('validation.attributes.status'),
            'email'                      => trans('validation.attributes.email'),
            'mobile'                     => trans('validation.attributes.phone'),
            'address'                    => trans('validation.attributes.address'),
            'hub_id'                     => trans('validation.attributes.hub_id'),
            'opening_balance'            => trans('validation.attributes.opening_balance'),
            'delivery_charge'            => trans('validation.attributes.delivery_charge'),
            'pickup_charge'              => trans('validation.attributes.pickup_charge'),
            'return_charge'              => trans('validation.attributes.return_charge'),
            'image_id'                   => trans('validation.attributes.image_id'),
            'driving_license_image_id'   => trans('validation.attributes.driving_license_image_id'),
        ];
    }


}
