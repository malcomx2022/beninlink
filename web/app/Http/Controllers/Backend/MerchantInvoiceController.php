<?php

namespace App\Http\Controllers\Backend;
 
use App\Exports\InvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Backend\InvoiceParcel;
use App\Repositories\Invoice\InvoiceInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;

class MerchantInvoiceController extends Controller
{
    protected $repo;
    public function __construct(InvoiceInterface $repo){
        $this->repo = $repo;
    }
    public function index($merchantId){
        $invoices    = $this->repo->merchantInvoiceGet($merchantId);
        $merchant_id = $merchantId;
        return view('backend.merchant.invoice.index',compact('invoices','merchant_id'));
    }

    public function InvoiceDetails($merchantId,$invoiceId){
        $invoice = $this->repo->merchantInvoiceDetails($merchantId,$invoiceId);
        $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->paginate(10);
        return view('backend.merchant.invoice.invoice_details', compact('invoice','invoiceParcels'));
    }

    public function StatusUpdate(Request $request,$merchant_id){

        if($this->repo->statusUpdate($request,$merchant_id)):
            Toastr::success(__('invoice.status_updated'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
 

    public function InvoiceCSV($merchant_id,$invoice_id){
       
        if($this->repo->invoiceGet($merchant_id,$invoice_id)):
            $invoice = $this->repo->invoiceGet($merchant_id,$invoice_id);
            $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->get();  
            return Excel::download(new InvoiceExport($invoiceParcels,$invoice),'invoice-'.$invoice->merchant->business_name.'-'.$invoice->invoice_date.'.xlsx');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif; 
    }
 
    public function InvoiceGenerateMenuallyIndex(){
        return view('backend.setting.invoice_generate.index');
    }

    public function InvoiceGenerateMenually(){
        try {
            Artisan::call('invoice:generate');
            Toastr::success(__('invoice.invoice_generated_successfully'),__('message.success'));
            return redirect()->back();
        } catch (\Throwable $th) {
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function PaidInvoice(Request $request){
        $invoices            = $this->repo->getPaidInvoices();
        $processInvoices     = $this->repo->getProcessInvoices();
        $unpaidInvoices      = $this->repo->getUnpaidInvoices();
        return view('backend.invoice.paid_invoice_list',compact('invoices','processInvoices','unpaidInvoices','request'));
    }
}
