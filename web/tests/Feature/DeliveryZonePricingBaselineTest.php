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
use App\Services\Pricing\ZoneCatalog;
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
 * Celle du jeu pilote, selon la décision du métier du 2026-09-06 : **les
 * tarifs actuels sont conservés** — Cotonou reprend `next_day`, Périphérie
 * `sub_city`, Intérieur `outside_city` — et le « jour même » devient un
 * **supplément global de 300 F**.
 *
 * L'étape 6 a retiré les colonnes le 2026-09-07. L'ancien étalon est parti
 * avec elles, après que le test pont eut vérifié que les deux barèmes
 * annonçaient bien le même prix : c'est ce qui a autorisé son retrait.
 */
class DeliveryZonePricingBaselineTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /**
     * Le barème, en zones. C'est **l'étalon** : ces montants ne doivent plus
     * bouger sans décision. Ce sont ceux du jeu pilote d'origine, à la
     * correspondance actée en D4 — Cotonou ← `next_day`, Périphérie ←
     * `sub_city`, Intérieur ← `outside_city`.
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

        // Jusqu'à l'étape 6, ce décor partait du barème hérité et le
        // convertissait : c'était le chemin réel d'une installation. Les
        // colonnes parties, il n'y a plus de conversion — la grille zonée
        // s'écrit directement, et c'est elle le référentiel.
        //
        // Les montants n'ont pas bougé d'un franc au passage : ce sont ceux
        // que la conversion produisait, et que le test pont vérifiait avant
        // que l'ancien étalon ne parte.
        DeliveryCharge::query()->delete();
        app(ZoneCatalog::class)->installer((int) settings()->id, self::SUPPLEMENT_JOUR_MEME);

        $zones = [
            DeliveryZone::COTONOU,
            DeliveryZone::PERIPHERIE,
            DeliveryZone::INTERIEUR,
        ];
        foreach (self::GRILLE_ZONES as $poids => $montants) {
            foreach ($zones as $rang => $code) {
                DeliveryCharge::forceCreate([
                    'company_id' => settings()->id,
                    'category_id' => $this->categoryId,
                    'zone_id' => $this->zone($code)->id,
                    'weight' => $poids,
                    'amount' => $montants[$rang],
                    'position' => $poids,
                    'status' => Status::ACTIVE,
                ]);
            }
        }
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
     *
     * Les trois montants sont ceux tranchés par le métier le 2026-09-06 ; ils
     * font partie de l'étalon au même titre que la grille nationale, et ne
     * doivent pas plus bouger sans décision.
     */
    public function test_le_forfait_de_chaque_pays_de_la_cedeao(): void
    {
        foreach (['TG' => 12000, 'NG' => 18000, 'BF' => 15000] as $pays => $forfait) {
            $this->assertEquals($forfait, $this->tarif(DeliveryZone::CEDEAO, 1, null, $pays), $pays);
            // Un forfait ne regarde pas le poids : 1 kg et 10 kg au même prix.
            $this->assertEquals($forfait, $this->tarif(DeliveryZone::CEDEAO, 10, null, $pays), $pays);
        }
    }

    /**
     * Un pays hors de la liste n'est **pas** facturé au hasard. Le Ghana est
     * membre de la CEDEAO et n'a pas de forfait : la zone ne lui applique rien
     * plutôt que d'emprunter le montant d'un voisin.
     */
    public function test_un_pays_hors_liste_na_pas_de_tarif(): void
    {
        $this->assertNull($this->tarif(DeliveryZone::CEDEAO, 1, null, 'GH'));
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
            // La zone d'export a sa propre clé depuis le 2026-09-06 : elle
            // n'emprunte pas le taux « hors ville », elle a le sien.
            DeliveryZone::CEDEAO => (float) $taux['cedeao'],
        ];

        foreach ($attendu as $code => $prevu) {
            $this->assertEquals($prevu, $calcul->codRateForZone($this->merchant, $this->zone($code)), $code);
        }
    }
}
