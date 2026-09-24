<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;

class DeliveryTypeController extends Controller
{

    public function index(){
        return view('backend.delivery_type.index');
    }

    public function status(Request $request){
        // S64 — une ECRITURE hors perimetre, et elle a echappe au filet S38 pour
        // une raison qui merite d'etre ecrite : son identifiant s'appelle `key`,
        // pas `*_id`, et `BodyIdentifierCoverageTest::estIdentifiant()` ne
        // reconnait que la seconde forme. Meme angle mort que le terme de
        // recherche qui a donne le filet S58.
        //
        // `configs` porte bien une `company_id` et le modele un
        // `scopeCompanywise` : sans lui, un administrateur BASCULAIT le reglage
        // d'une autre societe — activait ou desactivait un type de livraison
        // chez un transporteur concurrent.
        //
        // Hors perimetre `first()` rend `null` et la ligne suivante
        // dereferencait : 500 au lieu d'un refus (famille S15).
        $deliverytype            =  Config::companywise()->where('key',$request->key)->first();
        abort_if(blank($deliverytype), 404);

        if($deliverytype->value  == Status::ACTIVE){
            $deliverytype->value =  Status::INACTIVE;
        }else{
            $deliverytype->value =  Status::ACTIVE;
        }
        $deliverytype->save();
        return $deliverytype;
    }
}
