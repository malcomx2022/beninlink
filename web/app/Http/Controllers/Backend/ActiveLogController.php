<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActiveLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $logs = Activity::whereHas('causer',function($query){
            $query->where('company_id',settings()->id);
        })->orderBy('id','desc')->paginate(10);
       
        return view('backend.log.index',compact('logs'));
    }

    /**
     * Le detail d'une activite.
     *
     * ⚠️ `Activity::find($id)` ne portait AUCUN perimetre, alors qu'`index()`
     * juste au-dessus scope par la societe du causeur. Un operateur de la
     * societe A lisait donc le detail d'une activite de la societe B en changeant
     * l'identifiant dans l'URL : l'ancienne valeur et la nouvelle, champ par
     * champ, d'un autre transporteur. C'est la famille **S7**.
     *
     * Le perimetre est exactement celui de la liste : ce qu'on ne peut pas voir
     * dans la liste, on ne peut pas l'ouvrir. Les activites sans causeur (une
     * commande console, par exemple) n'apparaissent dans aucune des deux — le
     * socle en a decide ainsi pour `index()`, on ne change pas cette regle ici.
     */
    public function view($id){
        $logDetails = Activity::whereHas('causer', function ($query) {
            $query->where('company_id', settings()->id);
        })->find($id);

        abort_if(blank($logDetails), 404);

        return view('backend.log.view',compact('logDetails'));
    }
}
