<?php
namespace App\Repositories\Designation;

use App\Enums\UserType;
use App\Models\Backend\Designation;
use App\Repositories\Designation\DesignationInterface;
use Illuminate\Support\Facades\Auth;

class DesignationRepository implements DesignationInterface{
    public function all(){
        return Designation::where('company_id',settings()->id)->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Designation::where(['company_id'=>settings()->id,'id'=>$id])->first();
    }

    public function store($request){
        try {
            $designation         = new Designation();
            $designation->company_id   = settings()->id;
            $designation->title  = $request->title; 
            $designation->company_id = settings()->id;  
            $designation->status = $request->status;
            $designation->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $designation         = Designation::find($id);
            $designation->company_id   = settings()->id;
            $designation->title  = $request->title;
            $designation->company_id = settings()->id;  
            $designation->status = $request->status;
            $designation->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
       $designation =  Designation::find($id);
       if($designation->company_id == settings()->id):
            return Designation::destroy($id);
       endif;
       return false;
    }
}