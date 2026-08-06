<?php
namespace App\Repositories\Todo;
use App\Models\Backend\To_do;
use App\Models\User;
use App\Repositories\Todo\TodoInterface;
use App\Enums\TodoStatus;


class TodoRepository implements TodoInterface{
    public function all(){
        return To_do::companywise()->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return To_do::find($id);
    }

    public function store($request){
        try {
            $todo               = new To_do();
            $todo->company_id   = settings()->id;
            $todo->title        = $request->title;
            $todo->description  = $request->description;
            $todo->user_id      = $request->user_id ;
            $todo->date         = $request->date;
            $todo->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {
        try {
            $todo               = To_do::find($request->id);
            $todo->company_id   = settings()->id;
            $todo->title        = $request->title;
            $todo->description  = $request->description;
            $todo->user_id      = $request->user_id ;
            $todo->date         = $request->date;
            $todo->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function todoProcessing($id,$request){
        try {
            $todoProcessing           = To_do::find($id);
            if($todoProcessing->company_id == settings()->id):
                $todoProcessing->note     = $request->note;
                $todoProcessing->status   = TodoStatus::PROCESSING;
                $todoProcessing->save();
                return true;
            endif;
            return false;
        } catch (\Throwable $th) {
            return false;
        }

    }
    public function todoComplete($id,$request){
        try {
            $todoComplete         = To_do::find($id);
            if($todoComplete->company_id == settings()->id):
                $todoComplete->note   = $request->note;
                $todoComplete->status = TodoStatus::COMPLETED;
                $todoComplete->save();
                return true;
            endif;
            return false;
        } catch (\Throwable $th) {
            return false;
        }

    }

    public function delete($id){
        $todo = To_do::find($id);
        if($todo->company_id == settings()->id):
            return To_do::destroy($id);
        endif;
        return false;

    }
}
