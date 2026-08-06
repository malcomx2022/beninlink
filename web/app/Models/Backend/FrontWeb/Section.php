<?php

namespace App\Models\Backend\FrontWeb;

use App\Enums\SectionType;
use App\Models\Backend\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Section extends Model
{
    use HasFactory;

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'value', 'id');
    }

    public function getImageAttribute()
    {
        if (!empty($this->upload->original['original']) && File::exists(public_path($this->upload->original['original']))) {
            return static_asset($this->upload->original['original']);
        }
        if (SectionType::BANNER == $this->type && $this->key == 'banner') :
            return static_asset('frontend/images/banner.png');
        else :
            return static_asset('images/default/blank-image.jpg');
        endif;
    }

    public function getMyTypeAttribute()
    {
        switch ($this->type) {
            case SectionType::BANNER:
                return __('levels.banner');
                break;
            case SectionType::ACHIEVEMENT:
                return __('levels.happy_achievement');
                break;
            case SectionType::ABOUT:
                return __('levels.about_us');
                break;
            case SectionType::SUBSCRIBE:
                return __('levels.subscribe');
                break;
            case SectionType::APP_LINK:
                return __('levels.app_download_link');
                break;
            case SectionType::MAP_LINK:
                return __('levels.map_link');
                break;
            default:
                return '';
                break;
        }
    }

    public function scopeCompanyWise($query)
    {
        return $query->where('company_id', settings()->id);
    }
}
