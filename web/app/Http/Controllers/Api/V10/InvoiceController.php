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
        return new InvoiceDetailsResource($invoice);
    }
}
