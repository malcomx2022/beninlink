<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\InvoiceDetailsResource;
use App\Http\Resources\v10\InvoiceResource;
use App\Repositories\Invoice\InvoiceInterface;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $repo;
    public $data = [];
     public function __construct(InvoiceInterface $repo)
     {
        $this->repo    = $repo;
     }
     
    //invoice list
    public function invoiceLists() { 
        $invoice_list = $this->repo->invoiceLists(); 
        $invoice = InvoiceResource::collection($invoice_list);
        return $invoice;
    }
    public function invoiceDetails($invoice_id){   
        
        $invoice = $this->repo->getFind($invoice_id);
        // Le repository ne rend que les factures du marchand connecte : hors de
        // son perimetre, on repond 404 plutot que de laisser la Resource
        // exploser sur un modele absent.
        abort_if(blank($invoice), 404, __('invoice.not_found'));

        return new InvoiceDetailsResource($invoice);
    }
}
