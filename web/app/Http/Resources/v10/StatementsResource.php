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
            "amount"            => amountValue($this->amount),
            "currency"          => (string) settings()->currency,
            "type"              => (int)$this->type,
            "typeName"          => trans("AccountHeads.".$this->type),
            'created_at'        => dateTimeFormat($this->created_at),
            'updated_at'        => dateTimeFormat($this->updated_at),
        ];
    }
}
