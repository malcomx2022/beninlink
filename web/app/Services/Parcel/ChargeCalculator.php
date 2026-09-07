<?php

namespace App\Services\Parcel;

use App\Exceptions\UnpricedDeliveryException;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Models\Backend\Packaging;

/**
 * Calcul des montants d'un colis — **côté serveur, seule source de vérité**.
 *
 * Corrige le constat S2 de la cartographie : jusqu'ici le socle enregistrait
 * `json_decode($request->chargeDetails)` **tel quel**, c'est-à-dire des frais,
 * une TVA et un net à reverser calculés en JavaScript par le navigateur. Un
 * client qui modifiait ce champ choisissait sa propre facture.
 *
 * Les règles reproduisent **à l'identique** celles de `public/backend/js/parcel/create.js`
 * — c'était la seule spécification existante :
 *
 *   frais COD      = encaissement × taux COD du marchand (selon la zone)
 *   sous-total     = frais livraison + frais COD + emballage + fragile
 *   TVA            = sous-total × taux de TVA du marchand
 *   net à reverser = encaissement − (sous-total + TVA)
 *
 * ⚠️ `total_delivery_amount` porte le **sous-total hors TVA**, conformément au
 * comportement d'origine. Le changer modifierait les factures déjà émises.
 *
 * Aucune donnée monétaire ne vient plus de la requête : seuls des **choix**
 * (catégorie, poids, type de livraison, emballage) y sont lus, et chaque tarif
 * est relu en base.
 */
class ChargeCalculator
{
    /**
     * Clé de `merchants.cod_charges` correspondant à chaque zone (**D4**).
     *
     * Le métier a demandé de garder les taux actuels : les trois clés
     * existantes se rattachent aux trois zones nationales, une pour une. La
     * CEDEAO a reçu la sienne le 2026-09-06, avec son taux — **3 %** — après
     * être restée volontairement à zéro tant qu'aucun montant n'était fixé.
     *
     * Cette table est la **seule** correspondance zone → taux : le calcul et
     * la ressource d'API la lisent toutes les deux, plutôt que d'en tenir
     * chacune une copie qui dériverait.
     */
    public const COD_KEY_BY_ZONE = [
        DeliveryZone::COTONOU => 'inside_city',
        DeliveryZone::PERIPHERIE => 'sub_city',
        DeliveryZone::INTERIEUR => 'outside_city',
        DeliveryZone::CEDEAO => 'cedeao',
    ];

    public function __construct(private ?DeliveryChargeResolver $resolver = null)
    {
        $this->resolver ??= new DeliveryChargeResolver();
    }

    /**
     * @return array{
     *   delivery_charge: float, cod_charge: float, cod_amount: float,
     *   vat: float, vat_amount: float, packaging_amount: float,
     *   liquid_fragile_amount: float, total_delivery_amount: float,
     *   current_payable: float
     * }
     */
    public function calculate(
        Merchant $merchant,
        ?int $categoryId,
        $weight,
        float $cashCollection,
        ?int $packagingId = null,
        bool $fragileLiquid = false,
        ?int $zoneId = null,
        ?int $delayId = null,
        ?string $country = null
    ): array {
        // D4, étape 6 — il n'existe plus qu'un axe de tarification : la route.
        // Un colis sans zone n'a pas de tarif, et le dire vaut mieux que zéro.
        $zone = $zoneId === null ? null : DeliveryZone::companywise()->find($zoneId);

        if ($zone === null) {
            throw UnpricedDeliveryException::sansZone();
        }

        $deliveryCharge = $this->deliveryChargeParZone($merchant, $categoryId, $weight, $zone, $delayId, $country);
        $codRate = $this->codRateForZone($merchant, $zone);
        $codAmount = $this->percentage($cashCollection, $codRate);

        $packagingAmount = $this->packagingAmount($packagingId);
        // `settingHelper` lit la table `configs`, scopée par société.
        $fragileAmount = $fragileLiquid ? (float) settingHelper('fragile_liquid_charge') : 0.0;

        // Sous-total hors TVA — c'est bien ce que le socle stocke dans
        // `total_delivery_amount`.
        $subTotal = $deliveryCharge + $codAmount + $packagingAmount + $fragileAmount;

        // Taux du marchand, sinon celui de la société (décision métier 2026-09-05).
        $vatRate = VatRate::for($merchant);
        $vatAmount = $this->percentage($subTotal, $vatRate);

        return [
            'delivery_charge' => $deliveryCharge,
            'cod_charge' => $codRate,
            'cod_amount' => $codAmount,
            'vat' => $vatRate,
            'vat_amount' => $vatAmount,
            'packaging_amount' => $packagingAmount,
            'liquid_fragile_amount' => $fragileAmount,
            'total_delivery_amount' => $subTotal,
            'current_payable' => $cashCollection - ($subTotal + $vatAmount),
        ];
    }

    /**
     * Tarif d'une route : zone × tranche, plus le supplément du délai (**D4**).
     *
     * `resolveByZone()` rend `null` quand la route n'est pas tarifée — une zone
     * sans grille, ou un pays d'export sans forfait. On **refuse** alors le
     * calcul plutôt que de retomber sur les quatre colonnes : le colis a
     * explicitement une zone, lui facturer le tarif d'un autre axe reviendrait
     * à inventer un prix, et personne ne le verrait avant la facture.
     */
    private function deliveryChargeParZone(
        Merchant $merchant,
        ?int $categoryId,
        $weight,
        DeliveryZone $zone,
        ?int $delayId,
        ?string $country
    ): float {
        $montant = $this->resolver->resolveByZone($merchant->id, $categoryId, $weight, $zone->id, $delayId, $country);

        if ($montant === null) {
            throw UnpricedDeliveryException::pourZone($zone->id, $zone->isExport() ? $country : null);
        }

        return $montant;
    }

    /**
     * Taux COD du marchand pour une **zone** (D4).
     *
     * Le métier a demandé de garder les taux actuels : les trois clés
     * existantes se rattachent aux trois zones nationales, une pour une.
     * La CEDEAO n'en a pas — un encaissement à l'étranger n'a jamais été
     * tarifé ici — et rend donc 0 tant que le métier n'a rien fixé. Zéro,
     * pas le taux « hors ville » : on ne devine pas un prix.
     */
    public function codRateForZone(Merchant $merchant, DeliveryZone $zone): float
    {
        $rates = $merchant->cod_charges ?? [];
        $key = self::COD_KEY_BY_ZONE[$zone->code] ?? null;

        return $key === null ? 0.0 : (float) ($rates[$key] ?? 0);
    }

    private function packagingAmount(?int $packagingId): float
    {
        if (!$packagingId) {
            return 0.0;
        }
        $packaging = Packaging::companywise()->find($packagingId);

        return $packaging ? (float) $packaging->price : 0.0;
    }

    private function percentage(float $amount, float $rate): float
    {
        return $amount * ($rate / 100);
    }
}
