<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class NewsOffersResource extends JsonResource
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
            "title"             => $this->title,
            "author"            => (string) optional($this->user)->name,
            "description"       => strip_tags($this->description),
            "image"             => (string)$this->image,
            "status"            => (int)$this->status,
            "statusName"        => trans("status.".$this->status),
            'date'              => dateFormat($this->date),
        ];
    }

}
