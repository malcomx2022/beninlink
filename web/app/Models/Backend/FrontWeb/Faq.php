<?php

namespace App\Models\Backend\FrontWeb;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    public function getMyStatusAttribute()
    {
        if ($this->status == Status::ACTIVE) :
            return '<span class="badge badge-success">' . __('status.' . $this->status) . '</span>';
        else :
            return '<span class="badge badge-danger">' . __('status.' . $this->status) . '</span>';
        endif;
    }

    public function scopeCompanyWise($query)
    {
        return $query->where('company_id', settings()->id);
    }
}
