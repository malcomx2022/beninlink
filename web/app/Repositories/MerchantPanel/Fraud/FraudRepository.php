<?php
namespace App\Repositories\MerchantPanel\Fraud;
use App\Models\Backend\Fraud;
use App\Repositories\MerchantPanel\Fraud\FraudInterface;
use Illuminate\Support\Facades\Auth;

/**
 * S7 — la liste noire est celle de la société ; une fiche n'est modifiable
 * que par son auteur. Le socle lisait `Fraud::find($id)` nu et listait
 * les fiches de toutes les sociétés.
 */
class FraudRepository implements FraudInterface{
    /** Fiches de la société du compte connecté (consultation partagée). */
    private function companyFrauds(){
        return Fraud::companywise();
    }

    /** Fiches créées par le compte connecté (seules modifiables). */
    private function ownedFrauds(){
        return $this->companyFrauds()->where('created_by', Auth::user()->id);
    }

    public function all(){
        return $this->companyFrauds()->orderByDesc('id')->paginate(10);
    }

    public function filter(){
        return $this->ownedFrauds()->orderByDesc('id')->paginate(10);
    }

    public function check($request){
        return $this->companyFrauds()->where('phone','LIKE','%'. $request->phone .'%')->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return $this->ownedFrauds()->find($id);
    }

    public function store($request){
        try {
            $fraud                = new Fraud();
            $fraud->company_id    = settings()->id;
            $fraud->created_by    = Auth::user()->id;
            $fraud->phone         = $request->phone;
            $fraud->name          = $request->name;
            $fraud->details       = $request->details;
            $fraud->tracking_id   = $request->tracking_id;
            $fraud->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $fraud                = $this->ownedFrauds()->find($id);
            if (blank($fraud)) {
                return false;
            }
            $fraud->created_by    = Auth::user()->id;
            $fraud->phone         = $request->phone;
            $fraud->name          = $request->name;
            $fraud->details       = $request->details;
            $fraud->tracking_id   = $request->tracking_id;
            $fraud->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        return $this->ownedFrauds()->whereKey($id)->delete();
    }
}