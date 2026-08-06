<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActiveLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $logs = Activity::whereHas('causer',function($query){
            $query->where('company_id',settings()->id);
        })->orderBy('id','desc')->paginate(10);
       
        return view('backend.log.index',compact('logs'));
    }

    public function view($id){
        $logDetails  =  Activity::find($id);
        return view('backend.log.view',compact('logDetails'));
    }
}
