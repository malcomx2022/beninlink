<?php

namespace App\Exports;

use App\Http\Resources\InvoiceParcelResource;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill; 
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
 
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceExport implements FromCollection, WithHeadings, WithEvents, WithStyles, WithDrawings, WithColumnWidths
{
    
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $invoiceParcels,$invoice;
    public function __construct($invoiceParcels,$invoice)
    {
        $this->invoiceParcels = $invoiceParcels; 
        $this->invoice = $invoice;
    }
    public function headings(): array
    {
    
        return [
            'Customer Name',
            'Zone',
            'Status',
            'Date',
            'Amount/$',
            'Fees/$', 
        ];
    }

 

    public function collection()
    {   
        return InvoiceParcelResource::collection($this->invoiceParcels);  
    }

      

     public function registerEvents(): array
    {
         
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                // $merchant = Merchant::find($this->merchant_id);
                // $invoice = Invoice::where(['merchant_id' => $this->merchant_id, 'invoice_id' => $this->invoice_id])->select('id', 'invoice_id', 'parcels_id','invoice_date')->first();
                $event->sheet->appendRows(array(
                    [' ', ' ']
                ), $event);
                 
                //header
                $event->sheet->setCellValue('D1', 'Merchant Name:');
                $event->sheet->setCellValue('D2', 'Location:'); //total cash collection
                $event->sheet->setCellValue('D3', 'Date:'); //total delivery charges 

                $event->sheet->setCellValue('E1', @$this->invoice->merchant->business_name); //merchant name
                $event->sheet->setCellValue('E2', @$this->invoice->merchant->address); //location
                $event->sheet->setCellValue('E3', @$this->invoice->invoice_date); //date 

                //merge  
                $event->sheet->mergeCells('E1:F1');
                $event->sheet->mergeCells('E2:F2');
                $event->sheet->mergeCells('E3:F3');
                //header
                $event->sheet->setCellValue('A4', ' '); //merchant name
                $event->sheet->setAutoFilter('A5:F10');

            },
            // Array callable, refering to a static method.
            AfterSheet::class => function (AfterSheet $event) {

                // total calculation
                $total_rows = $event->sheet->getHighestRow();
                $last_row = $total_rows + 1;
                $event->sheet->setCellValue('A' . $last_row, 'Total='); 
                $event->sheet->setCellValue('E' . $last_row, '=SUM(E2:E' . $total_rows . ')'); //total piad out //amount
                $event->sheet->setCellValue('F' . $last_row, '=SUM(F2:F' . $total_rows . ')'); //total delivery charges / fees
                $event->sheet->getStyle($last_row)->applyFromArray([
                    'font' => ['bold' => true]
                ]);
                //end total calculation 

                // net amount and fees 
                $f_h_row = $last_row + 1;
                $event->sheet->setCellValue('A' . $f_h_row, 'Net Amount'); 
                $event->sheet->setCellValue('B' . $f_h_row, 'Total Fees'); //total piad out //amount
                $event->sheet->getStyle('A'.$f_h_row.':B'.$f_h_row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => Color::COLOR_WHITE]
                    ],
                    'fill' => ['fillType'   => Fill::FILL_SOLID, 'startColor' => ['argb' => '7e0095']],
                ]); 
 
                $f_v_row = $f_h_row + 1;
                $event->sheet->setCellValue('A' . $f_v_row, '=E'.$last_row.'-'.'F'.$last_row); 
                $event->sheet->setCellValue('B' . $f_v_row, '=F'.$last_row); //total piad out //amount
                $event->sheet->getStyle('A'.$f_v_row.':B'.$f_v_row)->applyFromArray([
                    'font' => [
                        'bold' => true, 
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'dae3f3'],
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '7e0095']
                        ], 
                    ],
                ]); 

                // end net amount and fees

              
                // signature rows
                    $a_sig_row = $f_h_row + 3; 
                    $event->sheet->setCellValue('A' . $a_sig_row, 'Accounting Signature:'); 
                    $event->sheet->mergeCells('A'.$a_sig_row.':B'.$a_sig_row);  
                    $event->sheet->getStyle('A'.$a_sig_row.':B'.$a_sig_row)->applyFromArray([ 
                        'font' => ['bold' => true]
                    ]); 


                    $m_sig_row = $f_h_row + 3;
                    $event->sheet->setCellValue('E' . $m_sig_row, 'Merchant Signature:'); 
                    $event->sheet->mergeCells('E'.$m_sig_row.':F'.$m_sig_row);  
                    $event->sheet->getStyle('E'.$m_sig_row.':F'.$m_sig_row)->applyFromArray([ 
                        'font' => ['bold' => true]
                    ]); 
 
 
                //end signature rows
                    
                //signature box 
                    $sb_sig_row = $m_sig_row + 4;   
                    $event->sheet->mergeCells('A'.($m_sig_row+1).':B'.$sb_sig_row);  
                    $event->sheet->mergeCells('E'.($m_sig_row+1).':F'.$sb_sig_row);   
                //end signature box


                $event->sheet->getStyle('A4:F'.($m_sig_row+4))->applyFromArray([ 
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        // 'wrapText' => true,
                    ]                    
                ]); 

            },

        ];
    }

    public function styles(Worksheet $sheet)
    {

        $styles = [
            // Style the first row as bold text.
            5        => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => Color::COLOR_WHITE]
                ],
                'fill' => ['fillType'   => Fill::FILL_SOLID, 'startColor' => ['argb' => '7e0095']],
            ],

        ];
        $total_rows = $sheet->getHighestRow() + 1;
        for ($i = 6; $i < $total_rows; $i++) {
            $modulas = $i % 2;
            if ($modulas == 1) :
                $styles[$i] =   [
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'dae3f3'],
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '7e0095']
                        ],

                    ],
                ];
            else :
                $styles[$i] =   [
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '7e0095']
                        ],

                    ],
                ];
            endif;
        }
 


        return $styles;
    }

    public function drawings()
    {
        if (!$imageResource = @imagecreatefromstring(file_get_contents(settings()->PLogoImage))) {
            throw new \Exception('The image URL cannot be converted into an image resource.');
        }
        $drawing = new MemoryDrawing();
        $drawing->setImageResource($imageResource);
        $drawing->setHeight(60);
        $drawing->setCoordinates('A1');
        return $drawing;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 20,
        ];
    }


}
