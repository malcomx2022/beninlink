<?php

namespace App\Http\Controllers\Backend;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PushNotification\StorePushNotificationRequest;
use App\Http\Requests\PushNotification\UpdatePushNotificationRequest;
use App\Http\Services\PushNotificationService;
use App\Models\User;
use App\Repositories\PushNotification\PushNotificationInterface;
use App\Repositories\Role\RoleInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    protected $repo;
    protected $role;
    public function __construct(PushNotificationInterface $repo,RoleInterface $role, PushNotificationService $pushNotificationService)
    {
        $this->repo = $repo;
        $this->role =$role;
    }

    public function index()
    {
        $pushNotifications = $this->repo->all();
        return view('backend.push-notification.index',compact('pushNotifications'));
    }

    public function create()
    {
        $roles        = $this->role->getRole();
        return view('backend.push-notification.create',compact('roles'));
    }

    public function store(StorePushNotificationRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('push-notification.added_msg'),__('message.success'));
            return redirect()->route('push-notification.index');
        }else{
            Toastr::error(__('push-notification.error_msg'),__('message.success'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $pushNotification = $this->repo->get($id);
        return view('backend.push-notification.edit',compact('pushNotification'));
    }

    public function update($id,UpdatePushNotificationRequest $request)
    {
        if($this->repo->update($id, $request)){
            Toastr::success(__('push-notification.update_msg'),__('message.success'));
            return redirect()->route('news-offer.index');
        }else{
            Toastr::error(__('push-notification.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('push-notification.delete_msg'),__('message.success'));
            return redirect()->back();
        }
        else{
            Toastr::error(__('push-notification.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function Users(Request $request){
        if($request->ajax()):
            $users = User::companywise()->where('name','like','%'.$request->search.'%')->where(function($query)use($request){
                if($request->userType != 'all'):
                    $query->where('role_id',$request->userType);
                endif;
            })->paginate(10);
            $response = [];
            foreach ($users as  $user) {
                $response [] = [
                    'id'  => $user->id,
                    'text'=> $user->name
                ];
            }
            return response()->json($response);
        endif;
    }

}
