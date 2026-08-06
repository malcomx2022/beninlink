<?php

namespace App\Repositories\FrontWeb\Blogs;

use App\Models\Backend\FrontWeb\Blog;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\File;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use Illuminate\Support\Facades\Auth;

class BlogsRepository implements BlogsInterface
{
    public function get()
    {
        return Blog::companyWise()->orderBy('position', 'asc')->paginate(10);
    }

    public function getActive($limit = null)
    {
        $take = $limit ?? 9;
        return Blog::companyWise()->active()->orderBy('position', 'asc')->paginate($take);
    }

    public function getLatestBlogs()
    {
        return Blog::companyWise()->active()->orderBy('id', 'desc')->limit(5)->get();
    }

    public function getFind($id)
    {
        return Blog::companyWise()->findOrFail($id);
    }

    public function store($request)
    {
        try {
            $blog              = new Blog();
            $blog->company_id     = settings()->id;
            $blog->title       = $request->title;
            if ($request->image) :
                $blog->image_id    = $this->imageStoreUpdate('', $request->image);
            endif;
            $blog->description = $request->description;
            $blog->position    = $request->position;
            $blog->status      = $request->status;
            $blog->created_by  = Auth::user()->id;
            $blog->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $blog           = $this->getFind($id);
            $blog->company_id     = settings()->id;
            $blog->title       = $request->title;
            if ($request->image) :
                $blog->image_id    = $this->imageStoreUpdate($blog->image_id, $request->image);
            endif;
            $blog->description = $request->description;
            $blog->position    = $request->position;
            $blog->status      = $request->status;
            $blog->created_by  = Auth::user()->id;
            $blog->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id)
    {
        return Blog::destroy($id);
    }

    // Image Store in Upload Model
    public function imageStoreUpdate($file_id = '', $file)
    {

        try {
            $file_name = '';
            if (!blank($file)) {
                if (!File::exists(public_path('uploads/blogs'))) :
                    File::makeDirectory(public_path('uploads/blogs'));
                endif;
                $destinationPath       = public_path('uploads/blogs');
                $img          = date('YmdHis') . "." . $file->getClientOriginalExtension();
                $file->move($destinationPath, $img);
                $file_name            = 'uploads/blogs/' . $img;
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

    public function viewcount($id)
    {
        try {
            $blog         = Blog::find($id);
            $blog->views += 1;
            $blog->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
