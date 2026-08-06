<?php
namespace App\Repositories\Support;

use App\Enums\UserType;
use App\Models\Backend\Support;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Models\Backend\Department;
use App\Models\Backend\SupportChat;
use App\Repositories\Support\SupportInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SupportRepository implements SupportInterface {
    // get all rows in Department model
    public function departments(){
        return Department::active()->orderBy('title')->get();
    }

    public function all(){
        return Support::where(function($query){
            $query->whereHas('user',function($query){
                if(isSuperadmin()):
                    $query->where('user_type',UserType::ADMIN);
                    $query->orWhere('user_type',UserType::SUPER_ADMIN); 
                else:
                    $query->companywise();
                endif;
            });
        })->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Support::find($id);
    }

    public function chats($id){
        return  SupportChat::where('support_id',$id)->orderByDesc('id')->get();
    }

    public function store($request){
        try {

            $support                    = new Support();
            $support->user_id           = Auth::User()->id;
            $support->department_id     = $request->department_id;
            $support->service           = $request->service;
            $support->priority          = $request->priority;
            $support->subject           = $request->subject;
            $support->description       = $request->description;
            $support->date              = $request->date;
            if(isset($request->attached_file) && $request->attached_file != null) {
                $support->attached_file = $this->file('',$request->attached_file);
            }

            $support->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id,$request)
    {
        try {
            $support                    =  Support::find($id);

            $support->user_id           = Auth::User()->id;
            $support->department_id     = $request->department_id;
            $support->service           = $request->service;
            $support->priority          = $request->priority;
            $support->subject           = $request->subject;
            $support->description       = $request->description;
            $support->date              = $request->date;
            if(isset($request->attached_file) &&$request->attached_file != null) {
                $support->attached_file = $this->file($support->attached_file,$request->attached_file);
            }
            $support->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }


    public function reply($request){
        try {

            $reply                = new SupportChat();
            $reply->support_id    = $request->support_id;
            $reply->user_id       = Auth::user()->id;
            $reply->message       = $request->message;
            if(isset($request->attached_file) && $request->attached_file != null) {
                $reply->attached_file = $this->file('',$request->attached_file);
            }
            $reply->save();
            return true;
        } catch (\Throwable $th) {

           return false;
        }
    }
    public function delete($id){
        return Support::destroy($id);
    }

    public function file($image_id = '', $image)
    {
        try {
            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/support');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/support/'.$profileImage;
            }
            if(blank($image_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                if(file_exists($upload->original))
                {
                    unlink($upload->original);
                }
            }
            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function statusUpdate($id,$request){
        try {
            $support         = $this->get($id);
            $support->status = $request->status;
            $support->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

}
