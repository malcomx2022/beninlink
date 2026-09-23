<?php
namespace App\Repositories\Support;

use App\Enums\UserType;
use App\Models\Backend\Support;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Models\Backend\Department;
use App\Models\Backend\SupportChat;
use App\Repositories\Support\SupportInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

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

    public function all(){
        return $this->ticketsVisibles()->orderByDesc('id')->paginate(10);
    }

    /**
     * S23 — le périmètre de la LISTE, réutilisable.
     *
     * `all()` filtrait déjà par la société de l'auteur du ticket (et laissait
     * passer les deux types d'administrateur pour un super-administrateur) ;
     * `get()` et `chats()`, eux, faisaient `find($id)` nu. Un opérateur de la
     * société A lisait donc le ticket d'une société B — sujet, description,
     * pièce jointe — **et tout son fil de discussion**, en changeant
     * l'identifiant dans l'URL.
     *
     * La règle est extraite ici pour que les trois lectures ne puissent plus
     * diverger à nouveau.
     */
    private function ticketsVisibles()
    {
        return Support::whereHas('user', function ($query) {
            if (isSuperadmin()):
                $query->where('user_type', UserType::ADMIN);
                $query->orWhere('user_type', UserType::SUPER_ADMIN);
            else:
                $query->companywise();
            endif;
        });
    }

    public function get($id){
        return $this->ticketsVisibles()->find($id);
    }

    public function chats($id){
        return  SupportChat::where('support_id',$id)
            ->whereIn('support_id', $this->ticketsVisibles()->select('id'))
            ->orderByDesc('id')->get();
    }

    public function store($request){
        try {
            // S47 — fermer le SELECTEUR ne ferme pas l'ECRITURE. La liste
            // deroulante ne propose plus que nos services ; rien n'oblige le
            // navigateur a s'y tenir. Meme lecon que S43, prise par l'autre
            // bout : la garde d'un ecran ne vaut pas garde de ce qu'il ecrit.
            if (filled($request->department_id)
                && blank(Department::companywise()->find($request->department_id))) {
                return false;
            }


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
            // S47 — fermer le SELECTEUR ne ferme pas l'ECRITURE. La liste
            // deroulante ne propose plus que nos services ; rien n'oblige le
            // navigateur a s'y tenir. Meme lecon que S43, prise par l'autre
            // bout : la garde d'un ecran ne vaut pas garde de ce qu'il ecrit.
            if (filled($request->department_id)
                && blank(Department::companywise()->find($request->department_id))) {
                return false;
            }

            // S29 — ⚠️ TROU DE S23 : ce lot-la avait scope les LECTURES du support
            // (`all`, `get`, `chats`) et laisse les ECRITURES nues. `Support::find($id)`
            // ici permettait de reecrire le ticket d'un autre transporteur — sujet,
            // description, piece jointe — et de s'en attribuer la paternite par le
            // `user_id` juste en dessous. Le `catch` vide rendait l'echec muet.
            $support                    =  $this->ticketsVisibles()->find($id);

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


    public function reply($request){
        try {

            // S51 — ⚠️ QUATRIEME PASSAGE DANS CE FICHIER, et `reply()` avait
            // survecu aux trois precedents. S23 a scope les LECTURES, S29 a
            // ferme `update()` et `destroy()`, S47 a ferme le `department_id`
            // de `store()`/`update()` — personne n'a regarde la reponse.
            //
            // `support_id` venait du corps, nu : on ecrivait un message dans le
            // fil de discussion du ticket d'un AUTRE transporteur, signe de
            // notre `user_id`. Le client d'en face le lisait dans son ticket.
            //
            // ⚠️ Le panneau marchand, lui, gardait deja ce chemin. C'est
            // l'inverse de l'asymetrie supposee en S47 : ici c'est le
            // BACK-OFFICE qui etait en retard sur le panneau.
            if (blank($this->ticketsVisibles()->find($request->support_id))) {
                return false;
            }

            $reply                = new SupportChat();
            $reply->support_id    = $request->support_id;
            $reply->user_id       = Auth::user()->id;
            $reply->message       = $request->message;
            if(isset($request->attached_file) && $request->attached_file != null) {
                $reply->attached_file = $this->file('',$request->attached_file);
            }
            $reply->save();
            return true;
        } catch (\Throwable $th) {

           return false;
        }
    }
    public function delete($id){
        // S29 — meme trou que `update()` : `Support::destroy($id)` etait nu, donc le
        // ticket d'un autre transporteur se supprimait en changeant l'identifiant.
        $support = $this->ticketsVisibles()->find($id);

        return $support ? $support->delete() : 0;
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

    public function statusUpdate($id,$request){
        try {
            $support         = $this->get($id);
            $support->status = $request->status;
            $support->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

}
