<?php

namespace App\Http\Controllers\Backend;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\CustomsRule;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Back-office douane — chantier 5.
 *
 * Deux ecrans : les alertes emises (ce que l'exploitation doit traiter) et le
 * referentiel des regles (ce que l'administration ajuste).
 *
 * Pas de repository : le socle en pose un par module, mais il n'y aurait ici
 * que deux requetes a y ranger. On en ajoutera un le jour ou une logique
 * justifiera l'indirection.
 *
 * Permissions : les routes reutilisent `parcel_read` et `parcel_update` plutot
 * que d'introduire `customs_*`. `PermissionSeeder` fait des `new Permission()`
 * sans garde d'unicite : le rejouer sur une installation existante dupliquerait
 * toutes les lignes, et une permission neuve qu'aucun role ne porte rendrait
 * l'ecran inaccessible a tout le monde. Les regles douanieres gouvernant les
 * colis, l'emprunt se defend — a revoir si le socle rend un jour ses
 * permissions rejouables.
 */
class CustomsController extends Controller
{
    /** Alertes de toutes les societes du locataire, filtrables par statut. */
    public function alerts(Request $request)
    {
        $status = $request->filled('status') ? (int) $request->status : CustomsAlertStatus::PENDING;

        $alerts = CustomsAlert::companywise()
            ->where('status', $status)
            ->with('parcel', 'merchant')
            ->orderByDesc('id')
            ->paginate(25);

        return view('backend.customs.alerts', compact('alerts', 'status'));
    }

    public function resolve($id)
    {
        $alert = CustomsAlert::companywise()->find($id);

        if (blank($alert)) {
            Toastr::error(__('customs.not_found'), __('message.error'));

            return redirect()->back();
        }

        $alert->status = CustomsAlertStatus::RESOLVED;
        $alert->resolved_at = now();
        $alert->save();

        Toastr::success(__('customs.resolved_msg'), __('message.success'));

        return redirect()->back();
    }

    public function rules()
    {
        $rules = CustomsRule::companywise()
            ->orderBy('country_name')->orderBy('goods_category')
            ->get();

        return view('backend.customs.rules', compact('rules'));
    }

    public function edit($id)
    {
        $rule = CustomsRule::companywise()->findOrFail($id);
        $levels = [
            CustomsLevel::INFO => __('customs.level_1'),
            CustomsLevel::WARNING => __('customs.level_2'),
            CustomsLevel::BLOCKING => __('customs.level_3'),
        ];

        return view('backend.customs.edit', compact('rule', 'levels'));
    }

    public function update(Request $request, $id)
    {
        $rule = CustomsRule::companywise()->findOrFail($id);

        $request->validate([
            'level' => ['required', 'integer', 'between:1,3'],
            'required_document' => ['nullable', 'string', 'max:191'],
            'message' => ['required', 'string'],
            'status' => ['required', 'integer', 'between:0,1'],
        ]);

        // Le pays et la categorie ne se modifient pas : ils identifient la regle
        // (index unique). En changer ferait silencieusement doublon avec une autre.
        $rule->fill($request->only('level', 'required_document', 'message', 'status'))->save();

        Toastr::success(__('customs.update_msg'), __('message.success'));

        return redirect()->route('customs.rules');
    }
}
