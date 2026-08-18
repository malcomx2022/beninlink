<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\CustomsAlertStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v10\CustomsAlertResource;
use App\Models\Backend\CustomsAlert;
use App\Services\Customs\CustomsService;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Alertes douanieres du marchand — chantier 5.
 *
 * Le controle « ce colis est-il autorise » ne vit PAS ici : il est rendu par
 * `POST parcel/quote`, que l'ecran de creation appelle deja a chaque changement.
 * Un endpoint de plus ferait un second aller-retour pour la meme question.
 *
 * Il reste donc deux besoins : le referentiel (pour proposer les pays et les
 * categories) et l'historique des alertes emises.
 */
class CustomsController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(private readonly CustomsService $customs)
    {
    }

    /** Pays et categories couverts par les regles en vigueur. */
    public function reference()
    {
        return $this->responseWithSuccess(__('customs.rules'), $this->customs->reference(), 200);
    }

    /**
     * Alertes du marchand connecte. `?status=1` en cours, `?status=2` traitees,
     * sans parametre : toutes, les plus recentes d'abord.
     */
    public function alerts(Request $request)
    {
        $alerts = CustomsAlert::companywise()
            ->where('merchant_id', Auth::user()->merchant?->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', (int) $request->status))
            ->with('parcel')
            ->orderByDesc('id')
            ->paginate(20);

        return $this->responseWithSuccess(
            __('customs.title'),
            ['alerts' => CustomsAlertResource::collection($alerts)],
            200
        );
    }

    /**
     * Marque une alerte comme traitee : le marchand declare avoir reuni le
     * document. Scope au marchand connecte — meme discipline que S14 et S17.
     */
    public function resolve($id)
    {
        $alert = CustomsAlert::companywise()
            ->where('merchant_id', Auth::user()->merchant?->id)
            ->find($id);

        if (blank($alert)) {
            return $this->responseWithError(__('customs.not_found'), [], 404);
        }

        $alert->status = CustomsAlertStatus::RESOLVED;
        $alert->resolved_at = now();
        $alert->save();

        return $this->responseWithSuccess(__('customs.resolved_msg'), ['alert' => new CustomsAlertResource($alert)], 200);
    }
}
