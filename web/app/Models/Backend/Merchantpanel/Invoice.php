<?php

namespace App\Models\Backend\Merchantpanel;

use App\Enums\BooleanStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Http\Resources\InvoiceParcelResource;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $casts  =['parcels_id'=>'array'];

    public function merchant (){
        return $this->belongsTo(Merchant::class,'merchant_id','id');
    }

    public function getParcelsAttribute(){
        if($this->parcels_id !==null):
            $parcels  = Parcel::companywise()->whereIn('id',$this->parcels_id)->get();
        else:
            $parcels  = [];
        endif;
        return $parcels;
    }


     //parcels with paginations
     public function getParcelsPaginationsAttribute(){
        if($this->parcels_id !==null):
            $parcels  = Parcel::companywise()->whereIn('id',$this->parcels_id)->paginate(20);
        else:
            $parcels  = [];
        endif;
        return $parcels;
    }


    public function getInvoiceParcelsExportAttribute(){
        if($this->parcels_id !==null):
            $parcels  = Parcel::companywise()->whereIn('id',$this->parcels_id)->get();
            return InvoiceParcelResource::collection($parcels);
        else:
            $parcels  = [];
        endif;
        return $parcels;
    }
 
    public function getMyStatusAttribute(){
        if($this->status     == InvoiceStatus::PAID):
            $status = '<span class="badge badge-success">'.__('invoice.'.InvoiceStatus::PAID).'</span>';
        elseif($this->status == InvoiceStatus::UNPAID):
            $status = '<span class="badge badge-danger">'.__('invoice.'.InvoiceStatus::UNPAID).'</span>';
        elseif($this->status == InvoiceStatus::PROCESSING):
            $status = '<span class="badge badge-primary">'.__('invoice.'.InvoiceStatus::PROCESSING).'</span>';
        endif;
        return $status;
    }

    public function getUpdateStatusAttribute(){
        $status     ='';
        if($this->status == InvoiceStatus::PROCESSING):
            $status .= '<a class="dropdown-item" href="'.route('merchant.invoice.status.update',[$this->merchant_id,'id'=>$this->id,'invoice_id'=>$this->invoice_id,'status'=>InvoiceStatus::PAID]).'" >'.__('invoice.'.InvoiceStatus::PAID).'</a>';
            $status .= '<a class="dropdown-item" href="'.route('merchant.invoice.status.update',[$this->merchant_id,'id'=>$this->id,'invoice_id'=>$this->invoice_id,'status'=>InvoiceStatus::UNPAID]).'" >'.__('invoice.'.InvoiceStatus::UNPAID).'</a>';
        elseif($this->status  == InvoiceStatus::UNPAID):
            $status .= '<a class="dropdown-item" href="'.route('merchant.invoice.status.update',[$this->merchant_id,'id'=>$this->id,'invoice_id'=>$this->invoice_id,'status'=>InvoiceStatus::PROCESSING]).'" >'.__('invoice.'.InvoiceStatus::PROCESSING).'</a>';
        endif;
        return $status;
    }

    public function getParcelsGroupByAttribute(){
        if($this->parcels_id !==null):
            $parcels  = Parcel::companywise()->whereIn('id',$this->parcels_id)->paginate(20);
            $parcels =  $parcels->groupBy('status');
        else:
            $parcels  = [];
        endif;
        return $parcels;
    }

    public function getInvoiceStatusAttribute(){
        if($this->status     == InvoiceStatus::PAID):
            $status = __('invoice.'.InvoiceStatus::PAID);
        elseif($this->status == InvoiceStatus::UNPAID):
            $status =__('invoice.'.InvoiceStatus::UNPAID);
        elseif($this->status == InvoiceStatus::PROCESSING):
            $status = __('invoice.'.InvoiceStatus::PROCESSING);
        endif;
        return $status;
    }
  
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    

    public function invoiceParcels(){
        return $this->hasMany(InvoiceParcel::class,'invoice_id','id');
    }

}
