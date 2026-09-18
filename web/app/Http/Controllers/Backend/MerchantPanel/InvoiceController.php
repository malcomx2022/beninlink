<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Repositories\Invoice\InvoiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected $repo;
    public function __construct(InvoiceInterface $repo){
        $this->repo = $repo;
    }
    public function index(){
        $invoices  = $this->repo->get();
        return view('backend.merchant_panel.invoice.index',compact('invoices'));
    }

    public function InvoiceDetails($invoiceId){

        // S29 — `InvoiceDetails()` est bien scope sur le marchand connecte, mais
        // hors perimetre il rend `null` et la ligne suivante dereferencait : 500 la
        // ou tout le reste du panneau repond 404 (meme famille que S15).
        $invoice = $this->repo->InvoiceDetails($invoiceId);
        abort_if(blank($invoice), 404);

        $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->paginate(10);
        return view('backend.merchant_panel.invoice.invoice_details', compact('invoice','invoiceParcels'));
    }

}
