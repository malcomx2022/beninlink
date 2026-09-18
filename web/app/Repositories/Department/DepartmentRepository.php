<?php
namespace App\Repositories\Department;

use App\Enums\UserType;
use App\Models\Backend\Department;
use App\Repositories\Department\DepartmentInterface;
use Illuminate\Support\Facades\Auth;

class DepartmentRepository implements DepartmentInterface{
    public function all(){
        return Department::where('company_id',settings()->id)->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Department::where(['company_id'=>settings()->id,'id'=>$id])->first();
    }

    public function store($request){
        try {
            $department          = new Department();
            $department->company_id   = settings()->id;
            $department->title   = $request->title;
            $department->company_id = settings()->id;
            $department->status  = $request->status;
            $department->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            // S29 — VOL DE LIGNE : recherche nue, puis `company_id` ecrase par la
            // societe connectee — la ligne d'une autre societe etait TRANSFEREE.
            $department               = Department::companywise()->find($id);

            if (blank($department)) {
                return false;
            }
            $department->company_id   = settings()->id;
            $department->title        = $request->title; 
            $department->company_id   = settings()->id;  
            $department->status       = $request->status;
            $department->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
       $department  = Department::find($id);
       if($department->company_id == settings()->id):
        return Department::destroy($id);
       endif;
       return false;

    }
}