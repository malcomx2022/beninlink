<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id"                => $this->id,
            "subject"           => $this->subject,
            "userName"          => $this->user->name,
            "userEmail"         => $this->user->email,
            "userMobile"        => $this->user->mobile,
            "department"        => $this->department->title,
            "service"           => $this->service,
            "priority"          => $this->priority,
            "description"       => $this->description,
            'date'              => (string) dateFormat($this->date),
        ];
    }

}
