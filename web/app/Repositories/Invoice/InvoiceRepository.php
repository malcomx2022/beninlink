<?php

namespace App\Repositories\Invoice;

use App\Enums\BooleanStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Invoice\InvoiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceRepository implements InvoiceInterface
{
    //merchant panel invoice
    public function get()
    {
        return Invoice::companywise()->where('merchant_id', Auth::user()->merchant->id)->orderByDesc('id')->paginate(10);
    }

    public function getPaidInvoices()
    {
        return Invoice::companywise()->where('status', InvoiceStatus::PAID)->orderByDesc('id')->paginate(10, ['*'], 'paid_invoice_page');
    }

    public function getProcessInvoices()
    {
        return Invoice::companywise()->where('status', InvoiceStatus::PROCESSING)->orderByDesc('id')->paginate(10, ['*'], 'processing_invoice_page');
    }
    public function getUnpaidInvoices()
    {
        return Invoice::companywise()->where('status', InvoiceStatus::UNPAID)->orderByDesc('id')->paginate(10, ['*'], 'unpaid_invoice_page');
    }

    public function InvoiceDetails($invoiceId)
    {
        return Invoice::where(['merchant_id' => Auth::user()->merchant->id, 'invoice_id' => $invoiceId])->first();
    }
    

    public function store($merchant_id){
            
        try {  
                  
                $merchantFind         = Merchant::find($merchant_id);
                $merchant_date        = strtotime(\Carbon\Carbon::today()->subDays($merchantFind->payment_period)->format('d-m-Y'));
                $invoiceFind          = Invoice::companywise()->where('merchant_id',$merchantFind->id)->get()->last();
                if($invoiceFind):
                    $strtotime        = strtotime(\Carbon\Carbon::parse($invoiceFind->created_at)->format('d-m-Y'));
                else:
                    $strtotime        = $merchant_date;
                endif;
                $invoiceAlreadyGenerated = Invoice::companywise()->where('merchant_id', $merchant_id)->whereBetween('created_at', [\Carbon\Carbon::today()->startOfDay(), \Carbon\Carbon::today()->endOfDay()])->get()->last();
                 
                if($strtotime <= $merchant_date && !$invoiceAlreadyGenerated):

                    $parcelDelivered        = Parcel::companywise()->where('merchant_id',$merchant_id)->where(function($query){
                        $query->whereIn('status',[ParcelStatus::DELIVERED]);
                        $query->orWhere('partial_delivered',BooleanStatus::YES);
                    })->where('invoice_id',null)->get();
                    
                    $returnparcels  = Parcel::companywise()->where('merchant_id',$merchant_id)->where(function($query){
                        $query->whereIn('status',[ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,ParcelStatus::RETURN_TO_COURIER]);
                        $query->orWhere('return_to_courier',1);
                    })->where('partial_delivered',BooleanStatus::NO)->where('invoice_id',null)->get();
                    $parcels        = $parcelDelivered->merge($returnparcels);

              
                    if(count($parcels) > 0){

                        //collected amount 
                        $total_collected_amount       = $parcelDelivered->sum('cash_collection');

                        //total delivery charge amount
                        $total_d_charge_amount = $parcelDelivered->sum('total_delivery_amount');
                        $total_vat_amount      = $parcelDelivered->sum('vat_amount'); 
                        $total_return_charges  = $returnparcels->sum('return_charges'); 
                        $total_charges_amount  = ($total_d_charge_amount + $total_vat_amount  + $total_return_charges);
                        //end total delivery charge amount 
                        
                        //total current payable                        
                        $parcel_currenct_payable      =  $parcelDelivered->sum('current_payable');//delivered + return parcel  
                        $return_charge                =  $returnparcels->sum('return_charges');//return  charges 
                        $total_current_payable        = ($parcel_currenct_payable - $return_charge); 
                        //end total current payable
                           
                        $invoice                  =  new Invoice();
                        $invoice->company_id      = settings()->id;
                        $invoice->merchant_id     =  $merchant_id;
                        $invoice->invoice_id      =  $this->invoiceId($merchant_id);
                        $invoice->invoice_date    =  Carbon::today()->format('d-m-Y');
                        $invoice->cash_collection =  $total_collected_amount?? 0;
                        $invoice->total_charge    =  $total_charges_amount?? 0;
                        $invoice->current_payable =  $total_current_payable?? 0;
                        // $invoice->parcels_id   =  $parcels->pluck('id')->toArray();
                        $invoice->save();

                        foreach ($parcels as $key => $parcel) {
                             
                            $cash_collection = $parcel->cash_collection; 
                            $returnStatus = [
                                ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
                                ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
                                ParcelStatus::RETURN_TO_COURIER
                            ];
                            $status  = $parcel->status;
                            if( in_array($parcel->status,$returnStatus) || $parcel->return_to_courier == BooleanStatus::YES){
                                $d_charge   =  0;
                                $r_charge   = $parcel->return_charges;
                                $vat_amount = 0;
                                $cod_amount = 0;
                                $total_charge_amount = $r_charge;
                               
                                if($parcel->partial_delivered == BooleanStatus::YES):
                                    $amount     =  $parcel->current_payable; 
                                    $vat_amount = $parcel->vat_amount;
                                    $cod_amount = $parcel->cod_amount;
                                    $total_charge_amount = $parcel->total_delivery_amount + $vat_amount; 
                                else: 
                                    $amount = (string) - $parcel->return_charges;
                                    $cash_collection = 0; 
                                    $status    = ParcelStatus::RETURN_TO_COURIER;
                                endif;
            
                            }else{
                                $d_charge   = $parcel->delivery_charge;
                                $r_charge   = 0;
                                $vat_amount = $parcel->vat_amount;
                                $cod_amount = $parcel->cod_amount;
                                $amount     = $parcel->current_payable;  
                                $total_charge_amount = $parcel->total_delivery_amount + $vat_amount;
                            }
                            
                            $invoiceParcel                        = new InvoiceParcel(); 
                            $invoiceParcel->company_id                  = settings()->id;
                            $invoiceParcel->invoice_id            = $invoice->id;
                            $invoiceParcel->parcel_id             = $parcel->id;
                            $invoiceParcel->parcel_status         = $status;
                            $invoiceParcel->collected_amount      = $cash_collection?? 0;
                            $invoiceParcel->total_delivery_amount = $d_charge?? 0;
                            $invoiceParcel->return_charge         = $r_charge?? 0;
                            $invoiceParcel->vat_amount            = $vat_amount?? 0;
                            $invoiceParcel->cod_amount            = $cod_amount?? 0;
                            $invoiceParcel->total_charge_amount   = $total_charge_amount?? 0;
                            $invoiceParcel->current_payable       = $amount?? 0;
                            $invoiceParcel->save();

                            $parcelFind               = Parcel::find($parcel->id);
                            $parcelFind->invoice_id   = $invoice->id;
                            $parcelFind->save();
                        }
 
                    }
                endif;  
        } catch (\Throwable $th) { 
            return false;
        }
    }
    

    private function invoiceId($merchant_id)
    {
        $merchant          = Merchant::find($merchant_id);
        $merchantId        = $merchant->id;
        $prefix            = Str::upper(settings()->invoice_prefix) . '-';
        $invoicecount      = Invoice::companywise()->get()->count();
        $invoice_id        = $prefix . $merchantId . ($invoicecount + 1);
        return $invoice_id;
    }

    //admin panel merchant invoice
    public function merchantInvoiceGet($merchantId)
    {
        return Invoice::companywise()->where('merchant_id', $merchantId)->orderByDesc('id')->paginate(10);
    }
    public function merchantInvoiceDetails($merchantId, $invoiceId)
    {
        return Invoice::companywise()->where(['merchant_id' => $merchantId, 'invoice_id' => $invoiceId])->first();
    }

    public function statusUpdate($request, $merchant_id)
    {
        try {
            $invoice  = Invoice::where([
                'id' => $request->id,
                'merchant_id' => $merchant_id,
                'invoice_id' => $request->invoice_id
            ])->first();

            if ($invoice) :
                $invoice->status  = $request->status;
                $invoice->save();
            else :
                return false;
            endif;
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    //end admin panel merchant invoice


    //both panel invoice print
    public function InvoicePdf($merchant_id, $invoice_id)
    {
        try {
            $invoice  = Invoice::companywise()->where(['merchant_id' => $merchant_id, 'invoice_id' => $invoice_id])->first();
            return $invoice;
        } catch (\Throwable $th) {
            return false;
        }
    }


    //get invoice
    public function invoiceGet($merchant_id, $invoice_id)
    {
        try {
            $invoice  = Invoice::companywise()->where(['merchant_id' => $merchant_id, 'invoice_id' => $invoice_id])->first();
            return $invoice;
        } catch (\Throwable $th) {
            return false;
        }
    }

    //get invoice
    public function getFind($id)
    {
        try {
            $invoice  = Invoice::find($id);
            return $invoice;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function invoiceLists()
    {
        $invoices = Invoice::companywise()->where('merchant_id', Auth::user()->merchant->id)->where('status', InvoiceStatus::PAID, InvoiceStatus::PROCESSING)->paginate(10);
        return $invoices;
    }
}
