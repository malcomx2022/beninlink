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
            "amount"            => (string) number_format($this->amount,2),
            "cash_collection"   => (string) number_format($this->cash_collection,2),
            "currency"          => (string) settings()->currency,
            "type"              => (int)$this->type,
            "typeName"          => trans("statementType.".$this->type),
            'created_at'        => $this->created_at->format('d M Y, h:i A'),
            'updated_at'        => $this->updated_at->format('d M Y, h:i A'),
        ];
    }

}
