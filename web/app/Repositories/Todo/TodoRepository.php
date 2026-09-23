<?php
namespace App\Repositories\Todo;
use App\Models\Backend\To_do;
use App\Models\User;
use App\Repositories\Todo\TodoInterface;
use App\Enums\TodoStatus;


class TodoRepository implements TodoInterface{
    use \App\Traits\GuardsForeignIdentifiers;

    public function all(){
        return To_do::companywise()->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        // S29 — lecture nue : la tache d'une AUTRE societe, et son assignation.
        return To_do::companywise()->find($id);
    }

    public function store($request){
        // S51 — la tache porte bien `company_id`, mais elle est ASSIGNEE a un
        // utilisateur nomme par le formulaire. Sans garde, on assignait une
        // tache a l'agent d'une autre societe, qui la voyait dans sa liste.
        if ($this->identifiantsHorsPerimetre($request, ['user_id' => User::class])) {
            return false;
        }

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
            // S29 — VOL DE LIGNE : recherche nue, puis `company_id` ecrase par la
            // societe connectee — la ligne d'une autre societe etait TRANSFEREE.
            $todo               = To_do::companywise()->find($request->id);

            if (blank($todo)) {
                return false;
            }
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
