<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatementsController extends Controller
{

    public function index(){
        $statements = MerchantStatement::where('merchant_id',auth()->user()->merchant->id)->orderByDesc('id')->paginate(10);
        $request = [];
        return view('backend.merchant_panel.statements.index',compact('statements','request'));
    }

    public function filter(Request $request){
        $id         = auth()->user()->merchant->id;
        // S52 — ⚠️ CE COMMENTAIRE CORRIGE MA PROPRE LECTURE. J'ai d'abord cru a un
        // ORACLE D'EXISTENCE : la recherche du colis etait nue, donc un numero de
        // suivi existant chez un voisin aurait pose le filtre (aucun releve) la ou
        // un numero inconnu l'aurait laisse de cote (tous nos releves) — et la
        // difference aurait renseigne sur l'existence du numero.
        //
        // C'est FAUX, et c'est le test qui l'a dit : quatre lignes plus bas, le
        // socle porte deja `if (tracking_id && blank($parcelID)) parcel_id = 0`.
        // Les deux branches rendent donc un ensemble VIDE, et rien ne fuit.
        //
        // Le `companywise()` reste : il rend la lecture correcte et ne coute rien.
        // Mais il ne ferme aucune fuite, et la route est exemptee pour ce motif.
        $parcelID   = Parcel::companywise()->where('tracking_id', $request->parcel_tracking_id)->first();
        $statements = MerchantStatement::where('merchant_id',$id)->orderByDesc('id')->where(function( $query ) use ( $request, $parcelID ) {
            if($request->date) {
                $date = explode('To', $request->date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }
            if($request->type) {
                $query->where('type',$request->type);
            }
            if(!blank($parcelID)) {
                $query->where(['parcel_id' => $parcelID->id]);
            }
            if($request->parcel_tracking_id && blank($parcelID)){
                $query->where(['parcel_id' => 0]);
            }
        })->paginate(10);
        return view('backend.merchant_panel.statements.index',compact('statements','request'));
    }
}
