<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Profile\UpdateRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Repositories\Profile\ProfileInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;

/**
 * S37 — le profil de l'utilisateur connecte.
 *
 * Le socle ne verifiait l'identifiant de l'URL qu'a UN endroit sur cinq, et
 * annoncait le refus comme une panne :
 *
 * | Methode | Ce que faisait le socle |
 * |---|---|
 * | `view($id)` | comparait a l'utilisateur connecte, puis **`abort(500)`** |
 * | `create($id)` | ignorait `$id` et servait MON formulaire |
 * | `changePassword($id)` | idem |
 * | `update($id)` | ecrivait MON profil, puis **redirigeait vers `$id`** |
 * | `updatePassword($id)` | idem |
 *
 * Deux defauts distincts, donc :
 *
 * 1. **Un refus d'acces annonce comme une erreur serveur.** `500` dit « la faute
 *    est chez nous » ; ici la requete etait parfaitement formee et simplement
 *    interdite. L'operateur voit une page de panne, la supervision compte une
 *    erreur applicative, et un test ne peut pas distinguer un refus d'un bug.
 * 2. **Une ecriture reussie qui finissait sur cette page de panne.** Les deux
 *    methodes d'ecriture agissaient sur le compte connecte — donc sans fuite —
 *    mais renvoyaient ensuite vers `profile.index` avec l'identifiant **de
 *    l'URL**. Modifier son profil depuis `/admin/profile/update/42` enregistrait
 *    bien, puis affichait une erreur 500.
 *
 * ⚠️ Le code retenu est **403**, pas 404. C'est un ecart assume avec la
 * convention du reste du chantier (S23 a S35 repondent 404 hors perimetre), et il
 * a une raison : la, cacher l'EXISTENCE de la ressource d'une autre societe fait
 * partie du cloisonnement. Ici l'identifiant est celui d'un compte de la meme
 * societe, que tout porteur de `user_read` voit deja dans la liste : il n'y a pas
 * d'existence a cacher, et « interdit » est la reponse exacte.
 *
 * Les cinq methodes passent par le meme garde. Aucun lien de l'application ne
 * change : tous passent deja `Auth::user()->id` — les quatre entrees de menu, les
 * deux boutons « modifier » et les quatre formulaires (verifie).
 */
class ProfileController extends Controller
{
    protected $repo;
    public function __construct(ProfileInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Ces ecrans ne montrent et ne modifient QUE le compte connecte : un
     * identifiant qui n'est pas le sien est interdit, et se dit.
     */
    private function refuserSiCeNestPasMoi($id): void
    {
        abort_if((int) $id !== (int) Auth::user()->id, 403);
    }

    public function view($id)
    {
        $this->refuserSiCeNestPasMoi($id);

        $user = $this->repo->get($id);

        return view('backend.profile.index',compact('user'));
    }

    public function create($id)
    {
        $this->refuserSiCeNestPasMoi($id);

        $user = $this->repo->get(auth()->user()->id);
        return view('backend.profile.update',compact('user'));
    }

    public function changePassword($id)
    {
        $this->refuserSiCeNestPasMoi($id);

        $user = $this->repo->get(auth()->user()->id);
        return view('backend.profile.change_password',compact('user'));
    }

    public function update($id, UpdateRequest $request)
    {
        $this->refuserSiCeNestPasMoi($id);

        if($this->repo->update(auth()->user()->id, $request)){
            Toastr::success('Profile updated successfully.',__('message.success'));
            // La destination vient de l'utilisateur CONNECTE, pas de l'URL : le
            // garde ci-dessus les rend egaux, et l'ecrire ainsi empeche le defaut
            // de revenir si quelqu'un relache un jour ce garde.
            return redirect()->route('profile.index', auth()->user()->id);
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function updatePassword($id, UpdatePasswordRequest $request)
    {
        $this->refuserSiCeNestPasMoi($id);

        $result = $this->repo->updatePassword(auth()->user()->id, $request);
        if($result == 1){
            Toastr::success('Password updated successfully',__('message.success'));
            return redirect()->route('profile.index', auth()->user()->id);
        }
        elseif($result == 0){
            Toastr::warning('Old password not match!',__('message.warning'));
            return redirect()->back()->withInput();
        }
        else
        {
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

}
