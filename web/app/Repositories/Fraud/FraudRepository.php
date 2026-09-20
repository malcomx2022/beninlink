<?php
namespace App\Repositories\Fraud;
use App\Models\Backend\Fraud;
use App\Repositories\Fraud\FraudInterface;
use Illuminate\Support\Facades\Auth;

class FraudRepository implements FraudInterface{
    public function all(){
        return Fraud::companywise()->orderByDesc('id')->paginate(10);
    }

    /**
     * S35 — lecture nue : le signalement de fraude d'un autre transporteur
     * s'ouvrait en changeant l'identifiant de l'URL. Il porte le nom, le
     * telephone et le motif d'un client mis en cause par une autre societe.
     */
    public function get($id){
        return Fraud::companywise()->find($id);
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
            // S35 — le meme identifiant, en ecriture : on reecrivait le
            // signalement d'une autre societe. Il vient du CORPS
            // (`PUT admin/fraud/update`), donc hors du champ du filet.
            $fraud                = Fraud::companywise()->find($id);
            if(blank($fraud)){
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
        // S35 — forme gardee correcte, mais `->company_id` sur `null` levait :
        // 500 la ou le module repond 404.
        return (bool) Fraud::companywise()->whereKey($id)->delete();
    }
}