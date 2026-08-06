<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\PushNotificationService;
use Illuminate\Http\Request;
use App\Models\User;

class WebNotificationController extends Controller
{
    public function store(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $user->web_token = $request->token;
        $user->save();
        try {
            $request->request->add(['device_token'  => $request->token, 'topic' => $user->email]);
             app(PushNotificationService::class)->fcmSubscribe($request);
        } catch (\Exception $exception) {
            return response()->json(['Something went wrong.']);
        }
        return response()->json(['Token successfully stored.']);
    }


}
