<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantProfile\UpdateRequest;
use App\Http\Requests\MerchantProfile\UpdatePasswordRequest;
use App\Repositories\MerchantProfile\MerchantProfileInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;

/**
 * S37 — le profil du marchand connecte. Meme constat, meme correctif que
 * `Backend\ProfileController` : voir sa note pour le detail et pour le choix de
 * **403** plutot que 404.
 *
 * ⚠️ Un troisieme cas de 500 ici, distinct du precedent : `get()` cherche le
 * marchand PAR son `user_id`. Un compte qui n'est pas marchand — un agent, un
 * livreur — passe donc le garde sur son propre identifiant, puis la vue
 * dereference `null`. Ce panneau n'a pas de garde de type d'utilisateur ; d'ou le
 * `abort_if(blank(...), 404)`, qui dit « pas de profil marchand ici » au lieu de
 * planter.
 */
class MerchantProfileController extends Controller
{
    protected $repo;
    public function __construct(MerchantProfileInterface $repo)
    {
        $this->repo = $repo;
    }

    /** Voir la note de `Backend\ProfileController::refuserSiCeNestPasMoi()`. */
    private function refuserSiCeNestPasMoi($id): void
    {
        abort_if((int) $id !== (int) Auth::user()->id, 403);
    }

    /** Le profil marchand du compte connecte — ou 404 s'il n'est pas marchand. */
    private function monProfilMarchand()
    {
        $merchat = $this->repo->get(auth()->user()->id);
        abort_if(blank($merchat), 404);

        return $merchat;
    }

    public function view($id) // auth id
    {
        $this->refuserSiCeNestPasMoi($id);

        $merchat = $this->monProfilMarchand();
        return view('backend.merchant_profile.index',compact('merchat'));
    }

    public function create($id) // user id
    {
        $this->refuserSiCeNestPasMoi($id);

        $merchat = $this->monProfilMarchand();
        return view('backend.merchant_profile.update',compact('merchat'));
    }

    public function changePassword($id)
    {
        $this->refuserSiCeNestPasMoi($id);

        // Un compte cree par Facebook ou Google n'a pas de mot de passe a changer :
        // regle du socle, conservee telle quelle.
        if(Auth::user()->facebook_id !== null || Auth::user()->google_id !== null):
            return redirect()->back();
        endif;
        $merchat = $this->monProfilMarchand();
        return view('backend.merchant_profile.change_password',compact('merchat'));
    }

    public function update($id, UpdateRequest $request)
    {
        $this->refuserSiCeNestPasMoi($id);

        if($this->repo->update(auth()->user()->id, $request)){
            Toastr::success('Merchant Profile updated successfully.',__('message.success'));
            // La destination vient de l'utilisateur CONNECTE, pas de l'URL.
            return redirect()->route('merchant-profile.index',auth()->user()->id);
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
            return redirect()->route('merchant-profile.index',auth()->user()->id);
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
