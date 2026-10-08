<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class IncomeExpenseResource extends JsonResource
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
            "parcel_id"         => $this->parcel_id,
            "note"              => $this->note,
            "date"              => dateFormat($this->date),
            "amount"            => amountValue($this->amount),
            "cash_collection"   => amountValue($this->cash_collection),
            "currency"          => (string) settings()->currency,
            "type"              => (int)$this->type,
            "typeName"          => trans("statementType.".$this->type),
            'created_at'        => dateTimeFormat($this->created_at),
            'updated_at'        => dateTimeFormat($this->updated_at),
        ];
    }

}
