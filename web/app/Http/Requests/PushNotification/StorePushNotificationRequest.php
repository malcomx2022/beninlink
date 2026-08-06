<?php

namespace App\Http\Requests\PushNotification;

use Illuminate\Foundation\Http\FormRequest;

class StorePushNotificationRequest extends FormRequest
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
            'title'        => ['required','string','max:191'],
            'description'  => ['required','string','max:1000'],
            'role_id'      => ['required','string'],
            'image'        => 'nullable|image|mimes:png|max:5098',
        ];
    }
}
