<?php

namespace App\Repositories\FrontWeb\Section;

use App\Enums\SectionType;
use App\Models\Backend\FrontWeb\Section;
use App\Models\Backend\Upload;
use App\Repositories\FrontWeb\Section\SectionInterface;
use Illuminate\Support\Facades\File;

class SectionRepository implements SectionInterface
{
    public function all()
    {
        return Section::companyWise()->with('upload')->select('*')->groupBy('type')->orderBy('id', 'asc')->paginate(10);
    }

    public function getFind($type)
    {
        $sections = Section::companyWise()->where('type', $type)->get();
        $array = [];
        foreach ($sections as  $section) {
            $array[$section->key] = $section->value;
            if ($section->type == SectionType::BANNER && $section->key == 'banner') :
                $array['banner_image'] = $section->image;
            endif;
        }
        return $array;
    }

    public function sectionType($type)
    {
        switch ($type) {
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
            default:
                return '';
                break;
        }
    }

    public function update($type, $request)
    {
        try {
            foreach ($request->data as $key => $value) {
                $section              = Section::companyWise()->where('type', $type)->where('key', $key)->first();
                if ($section) :
                    if ($key == 'banner') :
                        $section->value = $this->imageStoreUpdate($section->value, $value);
                    else :
                        $section->value   = $value;
                    endif;
                    $section->company_id     = settings()->id;
                    $section->save();
                endif;
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    // Image Store in Upload Model
    public function imageStoreUpdate($file_id = '', $file)
    {
        try {
            $file_name = '';
            if (!blank($file)) {
                if (!File::exists(public_path('uploads/section'))) :
                    File::makeDirectory(public_path('uploads/section'));
                endif;
                $destinationPath       = public_path('uploads/section');
                $img          = date('YmdHis') . "." . $file->getClientOriginalExtension();
                $file->move($destinationPath, $img);
                $file_name            = 'uploads/section/' . $img;
            }

            if (blank($file_id)) {
                $upload           = new Upload();
            } else {
                $upload           = Upload::find($file_id);
                if (file_exists(public_path($upload->original))) {
                    unlink(public_path($upload->original));
                }
            }
            $upload->original     = $file_name;
            $upload->save();
            return $upload->id;
        } catch (\Exception $e) {
            return null;
        }
    }
}
