<?php
namespace App\Repositories\MerchantPanel\Support;
use App\Models\Backend\Support;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Models\Backend\Department;
use App\Repositories\MerchantPanel\Support\SupportInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\Backend\SupportChat;

/**
 * S7 — un ticket n'est lisible et modifiable que par son auteur. Le socle
 * lisait `Support::find($id)` nu : les échanges d'un autre compte (et d'une
 * autre société) étaient accessibles en changeant l'identifiant.
 */
class SupportRepository implements SupportInterface {
    // get all rows in Department model
    public function departments(){
        // S47 — lecture NUE d'un catalogue de societe. Ce selecteur listait les
        // services de TOUTES les societes : leurs intitules internes
        // s'affichaient dans la liste deroulante du formulaire de ticket, et un
        // ticket pouvait porter le service d'une autre maison. La regle est
        // pourtant connue du socle — `UserRepository` sert le MEME catalogue en
        // `where('company_id', settings()->id)->active()`.
        return Department::companywise()->active()->orderBy('title')->get();
    }
    private function ownedSupports(){
        return Support::where('user_id', Auth::user()->id);
    }
    public function all(){
      
        return $this->ownedSupports()->orderByDesc('id')->paginate(10);
    }
    public function get($id){
        return $this->ownedSupports()->find($id);
    }
    public function chats($id){
        return  SupportChat::orderByDesc('id')->where('support_id',$id)
            ->whereIn('support_id', $this->ownedSupports()->select('id'))->get();
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
            $support                    =  $this->ownedSupports()->find($id);
            if (blank($support)) {
                return false;
            }
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
    public function delete($id){
        return $this->ownedSupports()->whereKey($id)->delete();
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

    public function reply($request){
        try {
            if (blank($this->get($request->support_id))) {
                return false;
            }
            $reply              = new SupportChat();
            $reply->support_id  = $request->support_id;
            $reply->user_id     = Auth::user()->id;
            $reply->message     = $request->message;
            if(isset($request->attached_file) && $request->attached_file != null) {
                $reply->attached_file = $this->file('',$request->attached_file);
            }
            $reply->save();
            return true;
        } catch (\Throwable $th) {
           return false;
        }
    }
}
