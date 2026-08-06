<?php

namespace App\Models\Backend\FrontWeb;

use App\Enums\Status;
use App\Models\Backend\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Service extends Model
{
    use HasFactory;

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'image_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    public function getImageAttribute()
    {
        if (!empty($this->upload->original['original']) && File::exists(public_path($this->upload->original['original']))) {
            return static_asset($this->upload->original['original']);
        }
        return static_asset('images/default/blank-image.jpg');
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
