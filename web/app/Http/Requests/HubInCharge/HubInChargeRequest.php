<?php

namespace App\Http\Requests\HubInCharge;

use App\Enums\Status;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\HubInCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HubInChargeRequest extends FormRequest
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
            'status'                      => ['required', 'numeric'],
            'user_id'                     => ['required', 'numeric'],
        ];
    }

    public function attributes()
    {
        return [
            'user_id'                    => trans('validation.attributes.user'),
            'status'                     => trans('validation.attributes.status'),
        ];
    }

    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
            if(request('status') == Status::ACTIVE)
            if ($this->userUniqueCheck()) {
                $validator->errors()->add('user_id', trans('validation.attributes.user_assigned'));
            }

            if ($this->userHubUniqueCheck()) {
                $validator->errors()->add('user_id', trans('validation.attributes.user_exists'));
            }

        });
    }

    private function userUniqueCheck()
    {
        $id                         = $this->id;
        $queryArray['user_id']      = request('user_id');
        $queryArray['status']       = Status::ACTIVE;

        $hubInCharge = HubInCharge::where($queryArray)->where('id', '!=', $id)->first();

        if (blank($hubInCharge)) {
            return false;
        }
        return true;
    }

    private function userHubUniqueCheck()
    {

        $id                         = $this->id;
        $queryArray['user_id']      = request('user_id');
        $queryArray['hub_id']       = $this->hubID;

        $hubInCharge = HubInCharge::where($queryArray)->where('id', '!=', $id)->first();

        if (blank($hubInCharge)) {
            return false;
        }
        return true;
    }
}
