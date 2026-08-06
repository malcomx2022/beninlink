<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class StatementsResource extends JsonResource
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
            "note"              => $this->note,
            "date"              => (string) dateFormat($this->date),
            "amount"            => (string) number_format($this->amount,2),
            "currency"          => (string) settings()->currency,
            "type"              => (int)$this->type,
            "typeName"          => trans("AccountHeads.".$this->type),
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }
}
