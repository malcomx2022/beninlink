<?php
namespace App\Repositories\PushNotification;
use App\Http\Services\PushNotificationService;
use App\Models\Backend\PushNotification;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Repositories\PushNotification\PushNotificationInterface;
use Illuminate\Support\Facades\Auth;

class PushNotificationRepository implements PushNotificationInterface{

    // get all PushNotification
    public function all(){
        return PushNotification::companywise()->with('upload')->orderByDesc('id')->paginate(10);
    }
    // get single row in PushNotification
    public function get($id){
        return PushNotification::companywise()->with('upload')->find($id);
    }
    // All request data store in PushNotification.
    public function store($request)
    {
        try {
            $pushNotification                   = new PushNotification();
            $pushNotification->company_id       = settings()->id;
            $pushNotification->title            = strip_tags($request->title);
            $pushNotification->description      = strip_tags($request->description);
            $pushNotification->user_id          = $request->user_id == ''?null:$request->user_id;
            $pushNotification->merchant_id      = $request->merchant_id;
            $pushNotification->type             = $request->role_id;

            if(isset($request->image) && $request->image != null)
            {
                $pushNotification->image_id = $this->file('', $request->image);
            }
            $pushNotification->save();

            try {
                if($request->role_id == 'all'){
                    $FcmUser = User::companywise()->whereNotIn('user_type',[1])->get();
                    if(!blank($FcmUser)){
                        foreach ($FcmUser as $item){
                            app(PushNotificationService::class)->sendPushNotification($pushNotification, $item->email,$request->role_id);
                        }
                    }
                    $FcmToken =  User::companywise()->whereNotIn('user_type',[1])->whereNotNull('web_token')->pluck('web_token')->all();
                    app(PushNotificationService::class)->sendWebNotification($pushNotification, false,$request->role_id,$FcmToken);
                }elseif($pushNotification->user_id){
                    $FcmToken =  User::companywise()->where('id',$pushNotification->user_id)->whereNotNull('web_token')->pluck('web_token')->all();
                    app(PushNotificationService::class)->sendWebNotification($pushNotification, false,$request->role_id,$FcmToken);
                    app(PushNotificationService::class)->sendPushNotification($pushNotification, $pushNotification->user->email,$request->role_id);
                }else {
                    $FcmUser = User::companywise()->where('user_type',$request->role_id)->get();
                    if(!blank($FcmUser)){
                        foreach ($FcmUser as $item){
                            app(PushNotificationService::class)->sendPushNotification($pushNotification, $item->email,$request->role_id);
                        }
                    }
                    $FcmToken =  User::companywise()->where('user_type',$request->role_id)->whereNotNull('web_token')->pluck('web_token')->all();
                    app(PushNotificationService::class)->sendWebNotification($pushNotification, false,$request->role_id,$FcmToken);
                }

            } catch (\Exception $exception) {
                dd($exception);
            }
            return true;
        }
        catch (\Exception $e) {
            dd($e);
            return false;
        }
    }
    // All request data update in PushNotification.
    public function update($id, $request)
    {
        try {

            $pushNotification                   = PushNotification::find($id);
            $pushNotification->title            = strip_tags($request->title);
            $pushNotification->description      = strip_tags($request->description);
            $pushNotification->user_id          = $request->user_id;
            $pushNotification->merchant_id      = $request->merchant_id;
            $pushNotification->type             = $request->type;

            if(isset($request->image) && $request->image != null)
            {
                $pushNotification->image_id = $this->file($pushNotification->image_id, $request->image);
            }
            $pushNotification->save();
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    // Delete single row in PushNotification Model
    public function delete($id){
        try {
            $pushNotification = PushNotification::with('upload')->find($id);
            if($pushNotification->company_id == settings()->id):
                if(file_exists($pushNotification->upload->original))
                    unlink($pushNotification->upload->original);
                    Upload::destroy($pushNotification->upload->id);
                $pushNotification->delete();
                return true;
            endif;
            return false;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    // Image Store in Upload Model
    public function file($file_id = '', $file)
    {
        try {
            $file_name = '';
            if(!blank($file)){
                $destinationPath       = public_path('uploads/pushNotification');
                $profileImage          = date('YmdHis') . "." . $file->getClientOriginalExtension();
                $file->move($destinationPath, $profileImage);
                $file_name            = 'uploads/pushNotification/'.$profileImage;
            }
            if(blank($file_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($file_id);
                if(file_exists($upload->original))
                {
                    unlink($upload->original);
                }
            }
            $upload->original     = $file_name;
            $upload->save();
            return $upload->id;
        }
        catch (\Exception $e) {
            return false;
        }
    }
}
