<?php
namespace App\Repositories\HubInCharge;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\HubInCharge;
use App\Models\Backend\Hub;
use App\Models\Backend\Upload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\HubInCharge\HubInChargeInterface;


class HubInChargeRepository implements HubInChargeInterface {

    /**
     * Les responsables d'entrepot de la SOCIETE connectee.
     *
     * S31 — `hub_incharges` ne porte pas de `company_id` : le perimetre passe par
     * l'entrepot, exactement comme `merchant_shops` passe par le marchand (S26).
     * Sans ce filtre, `where('hub_id', $hubID)` acceptait l'entrepot de n'importe
     * quelle societe.
     */
    private function responsablesDeLaSociete()
    {
        return HubInCharge::whereHas('hub', function ($query) {
            $query->companywise();
        });
    }

    /** L'entrepot vise appartient-il a la societe connectee ? */
    private function entrepotDeLaSociete($hubID)
    {
        return Hub::companywise()->find($hubID);
    }

    /** L'agent vise appartient-il a la societe connectee ? */
    private function agentDeLaSociete($userID)
    {
        return User::companywise()->find($userID);
    }

    public function all($hubID){
        return $this->responsablesDeLaSociete()->where('hub_id',$hubID)->with('user','hub')->orderByDesc('id')->get();

    }
    public function get($hubID,$id) {
        return $this->responsablesDeLaSociete()->where(['id'=>$id,'hub_id'=>$hubID])->first();
    }

    public function store($hubID,$request) {
       try {
           // S31 — ni l'entrepot ni l'agent n'etaient verifies : on pouvait nommer un
           // responsable sur l'entrepot d'une AUTRE societe, et `assignedHub()` juste
           // en dessous DESACTIVAIT alors ses responsables en place puis reecrivait le
           // `hub_id` de l'agent choisi.
           if (blank($this->entrepotDeLaSociete($hubID)) || blank($this->agentDeLaSociete($request->user_id))) {
               return false;
           }

           $inCharge                             = new HubInCharge();
           $inCharge->user_id                    = $request->user_id;
           $inCharge->hub_id                     = $hubID;
           $inCharge->status                     = $request->status;
           $inCharge->save();
           if($request->status == Status::ACTIVE){
               $this->assignedHub($hubID,$inCharge);
           }
           return true;
       } catch (\Exception $e) {
            return false;
        }
    }

    public function update($hubID,$id, $request) {
        try {
            // S31 — meme portee que `store()`, et la ligne visee doit etre la notre.
            if (blank($this->entrepotDeLaSociete($hubID)) || blank($this->agentDeLaSociete($request->user_id))) {
                return false;
            }

            $inCharge                             = $this->responsablesDeLaSociete()
                ->where(['id'=>$id,'hub_id'=>$hubID])->first();

            if (blank($inCharge)) {
                return false;
            }

            $inCharge->status                     = $request->status;
            $inCharge->user_id                    = $request->user_id;
            $inCharge->hub_id                     = $hubID;
            $inCharge->save();
            if($request->status == Status::ACTIVE) {
                $this->assignedHub($hubID,$inCharge);
            }
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id) {
        // S31 — `HubInCharge::destroy($id)` etait NU, sans meme le `hub_id` : le
        // responsable d'entrepot de n'importe quelle societe se supprimait.
        $inCharge = $this->responsablesDeLaSociete()->whereKey($id)->first();

        return $inCharge ? $inCharge->delete() : 0;
    }

    public function user_image($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/users');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/users/'.$profileImage;
            }
            if(blank($image_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                unlink($upload->original);
            }

            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }

    public  function assignedHub($hubID, $inCharge)
    {

        try {
            // S31 — cette rafle passe TOUS les autres responsables actifs de l'entrepot
            // a inactif. Non scopee, elle desactivait ceux d'une autre societe : une
            // interruption de service chez elle, sans trace exploitable.
            if (blank($this->entrepotDeLaSociete($hubID))) {
                return false;
            }

            $inChargesStatus = $this->responsablesDeLaSociete()->whereNotIn('user_id',[$inCharge->user->id])->whereNotIn('id',[$inCharge->id])->where(['hub_id'=>$hubID,'status'=>Status::ACTIVE])->get();
            if(!blank($inChargesStatus)){
                foreach ($inChargesStatus as $incChargeStatus){
                    $incChargeStatus->status = Status::INACTIVE;
                    $incChargeStatus->save();
                }
            }

            $inCharge->status = Status::ACTIVE;
            $inCharge->save();

            // S31 — et ici on reecrivait le `hub_id` d'un utilisateur sans verifier qu'il
            // est a nous : un agent d'une autre societe se retrouvait rattache a notre
            // entrepot, ou le notre au leur.
            $user                                 = $this->agentDeLaSociete($inCharge->user_id);

            if (blank($user)) {
                return false;
            }

            $user->hub_id                         = $hubID;
            $user->save();

            return true;
        }
        catch (\Exception $e) {
             return false;
        }
    }
    // get all rows in User model
    public function users(){
        // S31 — cette liste alimente le menu deroulant de l'ecran : sans filtre, elle
        // nommait les administrateurs de TOUS les transporteurs.
        return User::companywise()->where('user_type',UserType::ADMIN)->orderBy('id')->get();
    }

    // get all rows in Hub model
    public function hub($hubID){
        // S31 — `findOrFail` nu : la fiche de l'entrepot d'une autre societe.
        return Hub::companywise()->findOrFail($hubID);
    }

}
