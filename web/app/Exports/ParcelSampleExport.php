<?php

namespace App\Exports;

use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParcelSampleExport implements FromCollection, WithHeadings,WithEvents,WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function headings(): array
    {
        return [
            'merchant_id',
            'shop_id',
            'pickup_address',
            'pickup_phone',
            'customer_name',
            'customer_phone',
            'customer_address',
            'invoice_no',
            'category_id',
            'weight',
            'delivery_type_id',
            'cash_collection',
            'selling_price',
            'packaging_id',
            'liquid_fragile',
            'note'
        ];
    }
    public function collection()
    {

          $parcel = [
                [
                    'merchant_id'     => 'select',
                    'shop_id'          => 'select',
                    'pickup_address'   => 'Dhaka',
                    'pickup_phone'     => '015654455555',
                    'customer_name'    => 'Customer Name',
                    'customer_phone'   => '12345',
                    'customer_address' => 'Dhaka',
                    'invoice_no'       => '12345',
                    'category_id'      => 'select',
                    'weight'           => 'select',
                    'delivery_type_id' => 'selected',
                    'cash_collection'  => '200',
                    'selling_price'    => '100',
                    'packaging_id'     => 'select',
                    'liquid_fragile'   => 'select',
                    'note'             => 'lkasdfkj'
                ]
        ];

        return collect($parcel);
    }

    public function registerEvents(): array
    {
          return  [
            AfterSheet::class=>function(AfterSheet $event){

                // set dropdown column
                $merchant_column = 'A';
                $delivery_type_column = 'K';

                // set dropdown options
                $merchants  = Merchant::all()->pluck('id')->ToArray();
                $merchants  = array_map('strtolower', $merchants);

                //delivery types
                $delivery_types = [
                    'Same Day',
                    'Next Day',
                    'Sub City',
                    'Outside City'
                ];



                // delivery type column=======================================
                // set dropdown list for first data row
                $validation = $event->sheet->getCell("{$merchant_column}2")->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST );
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION );
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Merchant is not in list.');
                $validation->setPromptTitle('Select from list');
                $validation->setPrompt('Please select a value from the drop-down list.');
                $validation->setFormula1(sprintf('"%s"',implode(',',$merchants)));

                // // clone validation to remaining rows
                // for ($i = 3; $i <= 10; $i++) {
                //     $event->sheet->getCell("{$merchant_column}{$i}")->setDataValidation(clone $validation);
                // }
                // // set columns to autosize
                // for ($i = 1; $i <= 10; $i++) {
                //     $column = Coordinate::stringFromColumnIndex($i);
                //     $event->sheet->getColumnDimension($column)->setAutoSize(true);
                // }


                //end delivery type column========================================
                // delivery type column=======================================


                // // set dropdown list for first data row
                // $validation = $event->sheet->getCell("{$delivery_type_column}2")->getDataValidation();
                // $validation->setType(DataValidation::TYPE_LIST );
                // $validation->setErrorStyle(DataValidation::STYLE_INFORMATION );
                // $validation->setAllowBlank(false);
                // $validation->setShowInputMessage(true);
                // $validation->setShowErrorMessage(true);
                // $validation->setShowDropDown(true);
                // $validation->setErrorTitle('Input error');
                // $validation->setError('Delivery Type is not in list.');
                // $validation->setPromptTitle('Select from list');
                // $validation->setPrompt('Please select a value from the drop-down list.');
                // $validation->setFormula1(sprintf('"%s"',implode(',',$delivery_types)));
                // // clone validation to remaining rows
                // for ($i = 3; $i <= 10; $i++) {
                //     $event->sheet->getCell("{$delivery_type_column}{$i}")->setDataValidation(clone $validation);
                // }
                // // set columns to autosize
                // for ($i = 1; $i <= 10; $i++) {
                //     $column = Coordinate::stringFromColumnIndex($i);
                //     $event->sheet->getColumnDimension($column)->setAutoSize(true);
                // }
                // //end delivery type column========================================


            }
        ];

    }


    public function styles(Worksheet $sheet)
{
    return [
       // Style the first row as bold text.
       1    => ['font' => ['bold' => true]],
    ];
}

}
