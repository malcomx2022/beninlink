<?php

namespace App\Exports;

use App\Http\Resources\MerchantParcelExportResource;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MerchantParcelExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function __construct($merchantParcel)
    {
        $this->merchantParcel = $merchantParcel;
    }

    public function headings(): array
    {
        return [
            'Invoice ID',
            'Tracking ID',
            'Customer Name',
            'Customer Phone',
            'Customer Address',
            'Status',
            'Cash Collection (TK)',
            'Delivery Charge',
            'Vat',
            'COD',
            'Total Charge',
            'Payable'
        ];
    }

    public function collection()
    {
         return MerchantParcelExportResource::collection($this->merchantParcel);

    }
}
