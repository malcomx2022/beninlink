<?php

namespace Tests\Feature;

use App\Enums\DeliveryType;
use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\Merchant;
use App\Services\Parcel\ChargeCalculator;
use App\Services\Parcel\DeliveryChargeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **Étalon du barème** — ce que coûte un colis aujourd'hui, tranche par tranche.
 *
 * ## Pourquoi cet étalon existe
 *
 * **D4** propose de refondre le barème : une zone deviendrait une ligne
 * (Cotonou / Périphérie / Intérieur / CEDEAO) au lieu d'une colonne, et le
 * délai serait découplé du périmètre. La migration prévue promet une chose :
 * **aucun montant ne change**. Cette promesse ne se vérifie pas à l'œil sur
 * 274 occurrences réparties dans 25 fichiers — il faut un étalon, écrit
 * **avant** la refonte, qui dise ce que chaque tranche facture.
 *
 * Le jour où la refonte arrive, ce fichier ne bouge pas : si un montant se
 * déplace, c'est le test qui le dit, pas un marchand. Il a tenu à travers les
 * six étapes de D4 sans qu'une ligne y soit touchée.
 *
 * ## Sa relève est écrite
 *
 * L'étape 6 supprime les quatre colonnes : le sujet de cet étalon disparaît
 * avec elles. Il ne s'efface pas pour autant —
 * `DeliveryZonePricingBaselineTest` prend le relais et fixe les mêmes prix
 * dans le modèle par zones. Les deux coexistent tant que les deux barèmes
 * coexistent, et le second vérifie explicitement qu'ils disent la **même**
 * chose : c'est ce qui autorisera à retirer celui-ci le jour venu, plutôt que
 * de perdre la garantie en même temps que la colonne.
 *
 * ## La grille de référence
 *
 * Celle du jeu pilote (`PiloteDataset::GRID`), en FCFA entiers, avec ses
 * quatre colonnes actuelles — dont les libellés trahissent déjà le mélange
 * que D4 veut défaire : « jour même » et « lendemain » sont des **délais**,
 * « périphérie » et « intérieur » des **périmètres**.
 */
class DeliveryPricingBaselineTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** Tranche « jusqu'à N kg » => [jour même, lendemain, périphérie, intérieur]. */
    private const GRILLE = [
        1 => [1000, 800, 1500, 2500],
        3 => [1500, 1200, 2000, 3500],
        5 => [2000, 1700, 2800, 4500],
        10 => [3000, 2500, 4000, 6500],
    ];

    private Merchant $merchant;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        Sanctum::actingAs($this->merchant->user, ['merchant']);
        $this->categoryId = DeliveryCategory::firstOrFail()->id;

        DeliveryCharge::query()->delete();
        foreach (self::GRILLE as $poids => [$jourMeme, $lendemain, $peripherie, $interieur]) {
            DeliveryCharge::forceCreate([
                'company_id' => settings()->id,
                'category_id' => $this->categoryId,
                'weight' => $poids,
                'same_day' => $jourMeme,
                'next_day' => $lendemain,
                'sub_city' => $peripherie,
                'outside_city' => $interieur,
                'position' => $poids,
                'status' => Status::ACTIVE,
            ]);
        }
    }

    /**
     * La table complète : chaque poids tombe dans sa tranche, chaque type
     * dans sa colonne. Un poids sous la première tranche paie la première ;
     * au-delà de la dernière, la plus lourde s'applique (S9).
     */
    public static function poids(): array
    {
        return [
            'un demi-kilo → tranche 1 kg' => [0.5, 1],
            'un kilo pile' => [1, 1],
            'deux kilos → tranche 3 kg' => [2, 3],
            'trois kilos pile' => [3, 3],
            'quatre kilos → tranche 5 kg' => [4, 5],
            'cinq kilos pile' => [5, 5],
            'huit kilos → tranche 10 kg' => [8, 10],
            'dix kilos pile' => [10, 10],
            'quinze kilos → la plus lourde' => [15, 10],
        ];
    }

    /** @dataProvider poids */
    public function test_le_tarif_de_chaque_poids_et_de_chaque_type(float|int $poids, int $tranche): void
    {
        $resolveur = app(DeliveryChargeResolver::class);
        $attendu = self::GRILLE[$tranche];

        $types = [
            DeliveryType::SAMEDAY => $attendu[0],
            DeliveryType::NEXTDAY => $attendu[1],
            DeliveryType::SUBCITY => $attendu[2],
            DeliveryType::OUTSIDECITY => $attendu[3],
        ];

        foreach ($types as $type => $prix) {
            $this->assertEquals(
                $prix,
                $resolveur->resolve($this->merchant->id, $this->categoryId, $poids, $type),
                "poids {$poids}, type {$type}",
            );
        }
    }

    /**
     * Le prix vu par le calcul complet, TVA comprise : c'est lui que le
     * marchand lit sur son devis, et c'est lui qui ne doit pas bouger.
     */
    public function test_le_devis_complet_ne_bouge_pas(): void
    {
        $calcul = app(ChargeCalculator::class)->calculate(
            $this->merchant,
            DeliveryType::OUTSIDECITY,
            $this->categoryId,
            8,          // → tranche 10 kg
            50000,      // encaissement COD
            null,
        );

        // 6 500 F de livraison pour l'intérieur, TVA 18 % de la société (D1).
        $this->assertEquals(6500, (float) $calcul['delivery_charge']);
        $this->assertEquals(18, (float) $calcul['vat']);
        $this->assertEquals(
            round(((float) $calcul['total_delivery_amount']) * 0.18),
            round((float) $calcul['vat_amount']),
            'la TVA porte sur le total des frais',
        );
        $this->assertEquals(
            (float) $calcul['total_delivery_amount'] + (float) $calcul['vat_amount'],
            50000 - (float) $calcul['current_payable'],
            'le net du marchand est le COD moins les frais TTC',
        );
    }

    /**
     * Les quatre colonnes mélangent délai et périmètre — le constat qui
     * justifie D4, ici sous forme exécutable : deux « délais » et deux
     * « périmètres » partagent le même axe, donc on ne peut pas demander
     * « lendemain, à l'intérieur du pays » : le barème n'a pas de case.
     */
    public function test_l_axe_unique_ne_permet_pas_de_croiser_delai_et_perimetre(): void
    {
        $resolveur = app(DeliveryChargeResolver::class);

        $lendemain = $resolveur->resolve($this->merchant->id, $this->categoryId, 3, DeliveryType::NEXTDAY);
        $interieur = $resolveur->resolve($this->merchant->id, $this->categoryId, 3, DeliveryType::OUTSIDECITY);

        $this->assertEquals(1200, $lendemain);
        $this->assertEquals(3500, $interieur);

        // Il n'existe aucun identifiant pour « lendemain ET intérieur » :
        // au-delà des quatre colonnes, le résolveur rend 0.
        $this->assertEquals(0.0, $resolveur->resolve($this->merchant->id, $this->categoryId, 3, 5));
    }
}
