<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Services\Parcel\ChargeCalculator;
use App\Services\Parcel\DeliveryChargeResolver;
use App\Services\Pricing\ZoneGridConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **Étalon du barème par zones** — ce que coûte un colis, zone par zone.
 *
 * ## Pourquoi celui-ci existe, en plus de l'autre
 *
 * `DeliveryPricingBaselineTest` a été écrit **avant** la refonte D4 pour fixer
 * le prix des quatre colonnes héritées, et il n'a pas bougé d'une ligne à
 * travers les six étapes : c'est lui qui prouve qu'aucun montant n'a été
 * déplacé en chemin.
 *
 * L'étape 6 supprime ces colonnes. Son sujet disparaît donc avec elles — et
 * avec lui la garantie, si on se contentait de l'effacer. Cet étalon-ci prend
 * le relais : il fixe le prix du **nouveau** modèle, et il est écrit
 * **maintenant**, pendant que les deux barèmes coexistent et qu'on peut donc
 * vérifier qu'ils disent la même chose.
 *
 * Le jour de la bascule, l'ancien étalon part, celui-ci reste. Si un montant
 * se déplace ensuite, c'est ce fichier qui le dit, pas un marchand.
 *
 * ## La grille de référence
 *
 * Celle du jeu pilote, convertie par `ZoneGridConverter` selon la décision du
 * métier du 2026-09-06 : **les tarifs actuels sont conservés** — Cotonou reprend
 * `next_day`, Périphérie `sub_city`, Intérieur `outside_city` — et le
 * « jour même » devient un **supplément global de 300 F**.
 */
class DeliveryZonePricingBaselineTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** Barème du jeu pilote, forme héritée : [jour même, lendemain, périphérie, intérieur]. */
    private const GRILLE_HERITEE = [
        1 => [1000, 800, 1500, 2500],
        3 => [1500, 1200, 2000, 3500],
        5 => [2000, 1700, 2800, 4500],
        10 => [3000, 2500, 4000, 6500],
    ];

    /**
     * Le même barème, en zones. C'est **l'étalon** : ces montants ne doivent
     * plus bouger sans décision, et surtout pas au passage de l'étape 6.
     *
     * Tranche => [Cotonou, Périphérie, Intérieur].
     */
    private const GRILLE_ZONES = [
        1 => [800, 1500, 2500],
        3 => [1200, 2000, 3500],
        5 => [1700, 2800, 4500],
        10 => [2500, 4000, 6500],
    ];

    /** Supplément « jour même », global — décision du métier. */
    private const SUPPLEMENT_JOUR_MEME = 300;

    private Merchant $merchant;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        $this->categoryId = DeliveryCategory::firstOrFail()->id;

        Auth::login($this->merchant->user);

        // On part du barème hérité du jeu pilote, puis on convertit : l'étalon
        // décrit ainsi le chemin réel d'une installation, pas une grille écrite
        // à la main qui pourrait diverger de ce que la conversion produit.
        DeliveryCharge::query()->delete();
        foreach (self::GRILLE_HERITEE as $poids => [$jourMeme, $lendemain, $peripherie, $interieur]) {
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

        app(ZoneGridConverter::class)->convert((int) settings()->id, self::SUPPLEMENT_JOUR_MEME);
    }

    private function zone(string $code): DeliveryZone
    {
        return DeliveryZone::companywise()->where('code', $code)->firstOrFail();
    }

    private function tarif(string $codeZone, float|int $poids, ?int $delayId = null, ?string $pays = null): ?float
    {
        return app(DeliveryChargeResolver::class)->resolveByZone(
            $this->merchant->id,
            $this->categoryId,
            $poids,
            $this->zone($codeZone)->id,
            $delayId,
            $pays,
        );
    }

    /**
     * Même table de poids que l'étalon hérité : un poids sous la première
     * tranche paie la première ; au-delà de la dernière, la plus lourde
     * s'applique (S9). La règle n'a pas changé avec le modèle.
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
    public function test_le_tarif_de_chaque_poids_et_de_chaque_zone(float|int $poids, int $tranche): void
    {
        [$cotonou, $peripherie, $interieur] = self::GRILLE_ZONES[$tranche];

        $this->assertEquals($cotonou, $this->tarif(DeliveryZone::COTONOU, $poids), "Cotonou, {$poids} kg");
        $this->assertEquals($peripherie, $this->tarif(DeliveryZone::PERIPHERIE, $poids), "Périphérie, {$poids} kg");
        $this->assertEquals($interieur, $this->tarif(DeliveryZone::INTERIEUR, $poids), "Intérieur, {$poids} kg");
    }

    /**
     * Le pont entre les deux étalons : tant que les deux barèmes coexistent,
     * ils disent la même chose. C'est ce qui justifie de remplacer l'ancien
     * plutôt que de simplement l'effacer.
     */
    public function test_les_deux_baremes_disent_le_meme_prix(): void
    {
        $herite = app(DeliveryChargeResolver::class);

        foreach (array_keys(self::GRILLE_HERITEE) as $poids) {
            $this->assertEquals(
                $herite->resolve($this->merchant->id, $this->categoryId, $poids, 2),
                $this->tarif(DeliveryZone::COTONOU, $poids),
                "lendemain ↔ Cotonou, {$poids} kg",
            );
            $this->assertEquals(
                $herite->resolve($this->merchant->id, $this->categoryId, $poids, 3),
                $this->tarif(DeliveryZone::PERIPHERIE, $poids),
                "sous-ville ↔ Périphérie, {$poids} kg",
            );
            $this->assertEquals(
                $herite->resolve($this->merchant->id, $this->categoryId, $poids, 4),
                $this->tarif(DeliveryZone::INTERIEUR, $poids),
                "hors ville ↔ Intérieur, {$poids} kg",
            );
        }
    }

    /**
     * Le supplément est **global** : le même dans toutes les zones. C'est la
     * propriété qui empêche de revenir aux quatre colonnes, et c'est donc elle
     * qu'un étalon doit tenir.
     */
    public function test_le_supplement_de_delai_est_le_meme_partout(): void
    {
        $jourMeme = DeliveryDelay::companywise()->where('code', DeliveryDelay::SAME_DAY)->firstOrFail();
        $lendemain = DeliveryDelay::companywise()->where('code', DeliveryDelay::NEXT_DAY)->firstOrFail();

        foreach ([DeliveryZone::COTONOU, DeliveryZone::PERIPHERIE, DeliveryZone::INTERIEUR] as $code) {
            $base = $this->tarif($code, 3);

            $this->assertEquals($base + self::SUPPLEMENT_JOUR_MEME, $this->tarif($code, 3, $jourMeme->id), $code);
            $this->assertEquals($base, $this->tarif($code, 3, $lendemain->id), $code);
        }
    }

    /**
     * Ce que le modèle rend possible et que l'ancien interdisait : croiser un
     * **délai** et un **périmètre**. « Lendemain à l'intérieur du pays » n'avait
     * pas de case ; il en a une, et elle a un prix.
     */
    public function test_on_peut_enfin_croiser_delai_et_perimetre(): void
    {
        $jourMeme = DeliveryDelay::companywise()->where('code', DeliveryDelay::SAME_DAY)->firstOrFail();

        $this->assertEquals(3500, $this->tarif(DeliveryZone::INTERIEUR, 3));
        $this->assertEquals(3800, $this->tarif(DeliveryZone::INTERIEUR, 3, $jourMeme->id));
    }

    /**
     * La zone d'export se facture au **pays**, forfait, sans regarder le poids.
     * Et tant qu'un pays n'est pas tarifé, elle ne facture **rien** — elle ne
     * devine pas.
     */
    public function test_la_zone_dexport_facture_au_forfait_du_pays(): void
    {
        $cedeao = $this->zone(DeliveryZone::CEDEAO);

        $this->assertNull($this->tarif(DeliveryZone::CEDEAO, 1, null, 'TG'));

        app(DeliveryZoneInterface::class)->enregistrerPays($cedeao, [
            ['id' => '', 'code' => 'TG', 'name' => 'Togo', 'flat_amount' => 12000],
        ]);

        $this->assertEquals(12000, $this->tarif(DeliveryZone::CEDEAO, 1, null, 'TG'));
        $this->assertEquals(12000, $this->tarif(DeliveryZone::CEDEAO, 10, null, 'TG'));
        $this->assertNull($this->tarif(DeliveryZone::CEDEAO, 1, null, 'NG'));
    }

    /**
     * Le prix vu par le calcul complet, TVA comprise : c'est lui que le
     * marchand lit sur son devis, et c'est lui qui ne doit pas bouger.
     *
     * Même colis que l'étalon hérité — 8 kg vers l'intérieur, 50 000 F de COD —
     * pour que les deux fichiers se comparent ligne à ligne.
     */
    public function test_le_devis_complet_ne_bouge_pas(): void
    {
        $calcul = app(ChargeCalculator::class)->calculate(
            $this->merchant,
            4,
            $this->categoryId,
            8,          // → tranche 10 kg
            50000,      // encaissement COD
            null,
            false,
            $this->zone(DeliveryZone::INTERIEUR)->id,
        );

        // 6 500 F pour l'intérieur, comme avant la refonte. TVA 18 % (D1).
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
     * Le taux COD suit la **zone**, pas le type de livraison : c'est la
     * correspondance décidée par le métier, une clé pour une zone.
     */
    public function test_le_taux_cod_suit_la_zone(): void
    {
        $taux = $this->merchant->cod_charges;
        $calcul = app(ChargeCalculator::class);

        $attendu = [
            DeliveryZone::COTONOU => (float) $taux['inside_city'],
            DeliveryZone::PERIPHERIE => (float) $taux['sub_city'],
            DeliveryZone::INTERIEUR => (float) $taux['outside_city'],
            // La CEDEAO n'a jamais été tarifée : zéro, pas le taux « hors ville ».
            DeliveryZone::CEDEAO => 0.0,
        ];

        foreach ($attendu as $code => $prevu) {
            $this->assertEquals($prevu, $calcul->codRateForZone($this->merchant, $this->zone($code)), $code);
        }
    }
}
