<?php

namespace App\Http\Controllers\Backend;

use App\Enums\UserType;
use App\Exceptions\UnpricedDeliveryException;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Services\Parcel\ChargeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Devis d'un colis pour les ecrans de creation du back-office.
 *
 * Le socle calculait les montants en JavaScript (`public/backend/js/parcel/create.js`
 * et son jumeau du panneau marchand), puis les postait dans `chargeDetails` :
 * c'etait la faille S2. Depuis sa correction, le serveur recalcule tout et ignore
 * ce champ — mais l'ecran continuait d'AFFICHER son propre calcul. Deux baremes,
 * un seul opposable : ils finissent par diverger sans que personne le voie.
 *
 * Cette route rend donc a l'affichage le meme `ChargeCalculator` que celui qui
 * enregistre, sans rien ecrire. Meme service que `POST /api/v10/parcel/quote` ;
 * seule l'authentification change (session ici, jeton la-bas).
 *
 * Le marchand n'est lu dans la requete que pour un ADMIN : un marchand connecte
 * ne devise que pour lui-meme.
 */
class ParcelQuoteController extends Controller
{
    public function __invoke(Request $request, ChargeCalculator $calculator)
    {
        $merchant = Auth::user()->user_type == UserType::MERCHANT
            ? Merchant::companywise()->where('user_id', Auth::id())->first()
            : Merchant::companywise()->find($request->merchant_id);

        if (blank($merchant)) {
            return response()->json(['message' => __('parcel.error_msg')], 422);
        }

        $cashCollection = (float) $request->cash_collection;

        try {
            $charges = $calculator->calculate(
                $merchant,
                $request->category_id ? (int) $request->category_id : null,
                $request->weight,
                $cashCollection,
                $request->packaging_id ? (int) $request->packaging_id : null,
                $request->fragileLiquid == 'true' || $request->fragileLiquid == 'on',
                $request->zone_id ? (int) $request->zone_id : null,
                $request->delay_id ? (int) $request->delay_id : null,
                $request->destination_country
            );
        } catch (UnpricedDeliveryException $exception) {
            // D4 — la route n'est pas tarifee. Le devis le DIT : afficher un
            // zero laisserait croire a une livraison gratuite, et la creation
            // serait refusee plus tard sans que l'ecran l'ait annonce.
            return response()->json(['message' => __('delivery_zone.route_not_priced')], 422);
        }

        // `total_delivery_amount` est le sous-total HORS TVA (comportement du
        // socle) ; l'ecran affiche aussi le total TVA comprise.
        $charges['total_payable_charges'] = $charges['total_delivery_amount'] + $charges['vat_amount'];
        $charges['cash_collection'] = $cashCollection;

        // Chaines pretes a afficher : FCFA entier, formatees par le meme helper
        // que le reste de l'application. `vat` et `cod_charge` sont des TAUX et
        // ne figurent donc pas ici.
        $display = [];
        foreach ($charges as $key => $value) {
            if (in_array($key, ['vat', 'cod_charge'], true)) {
                continue;
            }
            $display[$key] = formatAmount($value, false);
        }

        return response()->json(['amounts' => $charges, 'display' => $display]);
    }
}
