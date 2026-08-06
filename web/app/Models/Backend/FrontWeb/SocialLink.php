<?php

namespace App\Models\Backend\FrontWeb;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use HasFactory;

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    public function getMyStatusAttribute()
    {
        if ($this->status == Status::ACTIVE) {
            $status = '<span class="badge badge-pill badge-success">' . trans("status." . $this->status) . '</span>';
        } else {
            $status = '<span class="badge badge-pill badge-danger">' . trans("status." . $this->status) . '</span>';
        }
        return $status;
    }

    public function scopeCompanyWise($query)
    {
        return $query->where('company_id', settings()->id);
    }
}
