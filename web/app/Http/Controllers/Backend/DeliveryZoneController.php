<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryZone\DelaisRequest;
use App\Http\Requests\DeliveryZone\GrilleRequest;
use App\Http\Requests\DeliveryZone\PaysRequest;
use App\Http\Requests\DeliveryZone\ZonesRequest;
use App\Models\Backend\DeliveryZone;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Écrans de saisie du barème par zones — **D4, étape 4**.
 *
 * Deux écrans, parce que ce sont deux gestes différents :
 *
 *  - `index` — le **référentiel** : les zones, les délais et leur supplément
 *    global, et les forfaits CEDEAO par pays. On y vient rarement ;
 *  - `grid` — la **grille** d'une catégorie : une ligne par tranche de poids,
 *    une colonne par zone. On y vient à chaque révision tarifaire.
 *
 * Permissions : les routes réutilisent `delivery_charge_*` plutôt que
 * d'introduire `delivery_zone_*`. `PermissionSeeder` fait des `new Permission()`
 * sans garde d'unicité — le rejouer sur une installation existante
 * dupliquerait toutes les lignes, et une permission neuve qu'aucun rôle ne
 * porte rendrait l'écran inaccessible à tout le monde. Une zone **est** du
 * barème de livraison : l'emprunt se défend. Même raisonnement que le
 * back-office douane.
 */
class DeliveryZoneController extends Controller
{
    public function __construct(private DeliveryZoneInterface $repo)
    {
    }

    public function index()
    {
        $zones = $this->repo->zones();
        $delais = $this->repo->delais();
        $zoneExport = $this->repo->zoneExport();
        $pays = $zoneExport ? $this->repo->pays($zoneExport) : collect();

        return view('backend.delivery_zone.index', compact('zones', 'delais', 'zoneExport', 'pays'));
    }

    public function zones(ZonesRequest $request)
    {
        $resultat = $this->repo->enregistrerZones($request->input('zones', []));

        // Une zone refusée n'est pas une erreur de saisie : c'est une zone qui
        // porte encore des tarifs. Le dire, plutôt que de supprimer en silence
        // des lignes que `nullOnDelete` transformerait en lignes héritées.
        if ($resultat['refusees'] !== []) {
            Toastr::warning(
                __('delivery_zone.delete_refused', ['zones' => implode(', ', $resultat['refusees'])]),
                __('message.warning')
            );
        } else {
            Toastr::success(__('delivery_zone.zones_saved'), __('message.success'));
        }

        return redirect()->route('delivery-zone.index');
    }

    public function delais(DelaisRequest $request)
    {
        $this->repo->enregistrerDelais($request->input('delays', []));
        Toastr::success(__('delivery_zone.delays_saved'), __('message.success'));

        return redirect()->route('delivery-zone.index');
    }

    public function pays(PaysRequest $request, $id)
    {
        $zone = DeliveryZone::companywise()->find($id);

        if (blank($zone)) {
            Toastr::error(__('delivery_zone.zone_not_found'), __('message.error'));

            return redirect()->route('delivery-zone.index');
        }

        $this->repo->enregistrerPays($zone, $request->input('countries', []));
        Toastr::success(__('delivery_zone.countries_saved'), __('message.success'));

        return redirect()->route('delivery-zone.index');
    }

    public function grid(Request $request)
    {
        $categories = $this->repo->categories();
        $categoryId = $request->filled('category')
            ? (int) $request->category
            : optional($categories->first())->id;

        $zones = $this->repo->zones();
        $delais = $this->repo->delais();
        $tranches = $this->repo->tranches($categoryId);

        return view('backend.delivery_zone.grid', compact('categories', 'categoryId', 'zones', 'delais', 'tranches'));
    }

    public function gridUpdate(GrilleRequest $request)
    {
        $categoryId = (int) $request->category;
        $ecrites = $this->repo->enregistrerGrille($categoryId, $request->input('rows', []));

        Toastr::success(__('delivery_zone.grid_saved', ['count' => $ecrites]), __('message.success'));

        return redirect()->route('delivery-zone.grid', ['category' => $categoryId]);
    }
}
