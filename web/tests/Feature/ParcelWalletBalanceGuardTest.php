<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelLogs;
use App\Models\Backend\Wallet;
use App\Models\MerchantShops;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Services\Parcel\ChargeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use App\Models\Backend\DeliveryZone;
use Tests\TestCase;

/**
 * Le solde du portefeuille est un plancher à la création d'un colis.
 *
 * La règle n'est pas neuve : le socle refusait déjà la création quand les frais
 * dépassaient `wallet_balance`, dans `Backend\ParcelController::store()` et dans
 * `MerchantPanel\MerchantParcelController::store()`. Mais elle vivait dans ces
 * deux écrans seulement. Les trois autres chemins de création — l'API mobile et
 * les deux duplications — créaient le colis et débitaient un solde qui passait
 * en négatif, sans limite. Un marchand pouvait accumuler des courses qu'il
 * n'avait pas les moyens de payer, et le transporteur ne s'en apercevait qu'au
 * relevé.
 *
 * Le contrôle est donc descendu là où le débit a lieu — `Services\Parcel\WalletDebit`,
 * point unique désormais partagé par les quatre chemins — et sous le même
 * verrou que lui : sans cela, deux créations simultanées lisent le même solde,
 * le trouvent toutes deux suffisant, et débitent deux fois.
 *
 * Ce qui n'a pas changé, volontairement : un marchand qui ne règle pas par
 * portefeuille n'est jamais bloqué, et la comparaison porte sur le sous-total
 * HORS TVA (`total_delivery_amount`) — la convention du socle, qui est aussi le
 * montant réellement prélevé.
 */
class ParcelWalletBalanceGuardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->merchant = Merchant::firstOrFail();
        $this->merchant->wallet_use_activation = Status::ACTIVE;
        $this->merchant->save();
    }

    /**
     * Les frais que le serveur appliquera au colis du scénario, au franc près.
     *
     * Le marchand est connecté avant le calcul : `ChargeCalculator` relit la
     * zone en `companywise()` — une garantie voulue, de la même famille que
     * S8 — et le scope retomberait sinon sur la société 1.
     */
    private function frais(): float
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        $zoneId = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', DeliveryZone::COTONOU)->value('id');

        return (float) app(ChargeCalculator::class)->calculate(
            $this->merchant->fresh(),
            1,
            1,
            50000.0,
            null,
            false,
            $zoneId,
        )['total_delivery_amount'];
    }

    private function avecUnSolde(float $solde): void
    {
        $this->merchant->wallet_balance = $solde;
        $this->merchant->save();
    }

    private function soldeActuel(): float
    {
        return (float) Merchant::find($this->merchant->id)->wallet_balance;
    }

    private function creerUnColis()
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        return $this->postJson('/api/v10/parcel/store', [
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('company_id', Merchant::firstOrFail()->company_id)->where('code', DeliveryZone::COTONOU)->value('id'),
            'cash_collection' => 50000,
            'weight' => 1,
            'shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000031',
            'customer_address' => 'Cotonou, Akpakpa',
        ], ['apiKey' => self::API_KEY]);
    }

    /**
     * Le constat qui ouvre ce fichier : depuis l'app, il manquait un franc et
     * le colis partait quand même.
     */
    public function test_the_api_refuses_a_parcel_the_wallet_cannot_pay(): void
    {
        $frais = $this->frais();
        $this->avecUnSolde($frais - 1);

        $this->creerUnColis()->assertStatus(422);

        // Rien n'a été écrit : ni le colis, ni le débit, ni l'écriture de
        // portefeuille qui l'accompagne.
        $this->assertSame(0, Parcel::count());
        $this->assertSame(0, Wallet::count());
        $this->assertSame($frais - 1, $this->soldeActuel());
    }

    /**
     * Un refus n'est utile que s'il dit quoi faire. L'app affiche le manque et
     * propose le bon montant de recharge.
     */
    public function test_the_refusal_says_what_is_missing(): void
    {
        $frais = $this->frais();
        $this->avecUnSolde($frais - 2500);

        $reponse = $this->creerUnColis()->assertStatus(422);

        $reponse->assertJsonPath('success', false);
        $reponse->assertJsonPath('data.missing', 2500);
        $reponse->assertJsonPath('data.required', (int) round($frais));
        $reponse->assertJsonPath('data.wallet_balance', (int) round($frais - 2500));
        $this->assertStringContainsString('Solde insuffisant', $reponse->json('message'));
    }

    /**
     * La frontière est bien « strictement supérieur », comme dans les deux
     * écrans du socle : un solde exactement égal aux frais passe et tombe à
     * zéro. Décaler d'un franc reviendrait à changer la règle en silence.
     */
    public function test_an_exact_balance_is_enough(): void
    {
        $this->avecUnSolde($this->frais());

        $this->creerUnColis()->assertOk();

        $this->assertSame(1, Parcel::count());
        $this->assertSame(0.0, $this->soldeActuel());
    }

    /**
     * Le plancher ne concerne que les marchands au portefeuille. Les autres
     * règlent au relevé : les bloquer sur un solde à zéro qu'ils n'utilisent
     * pas serait une régression, pas une protection.
     */
    public function test_a_merchant_who_does_not_pay_by_wallet_is_never_blocked(): void
    {
        $this->merchant->wallet_use_activation = Status::INACTIVE;
        $this->merchant->wallet_balance = 0;
        $this->merchant->save();

        $this->creerUnColis()->assertOk();

        $this->assertSame(1, Parcel::count());
        $this->assertSame(0.0, $this->soldeActuel());
    }

    /**
     * Le fond du sujet : quel que soit le nombre de tentatives, le solde ne
     * descend jamais sous zéro. C'était exactement ce qui manquait — rien
     * n'arrêtait la descente.
     */
    public function test_the_balance_never_goes_below_zero_however_many_attempts(): void
    {
        $frais = $this->frais();
        $this->avecUnSolde($frais * 2.5);

        $acceptes = 0;
        for ($i = 0; $i < 6; $i++) {
            if ($this->creerUnColis()->getStatusCode() === 200) {
                $acceptes++;
            }
            $this->assertGreaterThanOrEqual(0, $this->soldeActuel(), 'Le solde est passe en negatif.');
        }

        // Deux colis payés, le troisième refusé faute de quoi le payer.
        $this->assertSame(2, $acceptes);
        $this->assertSame(2, Parcel::count());
        $this->assertSame($frais * 0.5, $this->soldeActuel());
    }

    /**
     * Dupliquer un colis, c'est en créer un. Le socle ne contrôlait le solde
     * sur aucun des deux chemins de duplication.
     */
    public function test_duplicating_a_parcel_is_refused_when_the_wallet_cannot_pay(): void
    {
        $frais = $this->frais();
        $this->avecUnSolde($frais);

        $this->creerUnColis()->assertOk();
        $this->assertSame(0.0, $this->soldeActuel());

        $original = Parcel::firstOrFail();

        $this->expectException(InsufficientWalletBalance::class);

        try {
            $this->dupliquer($original);
        } finally {
            // Et surtout : la duplication refusée ne laisse aucun colis
            // derrière elle. Elle n'était pas transactionnelle avant.
            $this->assertSame(1, Parcel::count());
            $this->assertSame(0.0, $this->soldeActuel());
        }
    }

    /** La duplication passe tant que le solde suit. */
    public function test_duplicating_a_parcel_debits_the_wallet(): void
    {
        $frais = $this->frais();
        $this->avecUnSolde($frais * 2);

        $this->creerUnColis()->assertOk();
        $this->assertTrue($this->dupliquer(Parcel::firstOrFail()));

        $this->assertSame(2, Parcel::count());
        $this->assertSame(0.0, $this->soldeActuel());

        // Au passage : la duplication depuis le panneau marchand echouait a
        // tous les coups sur une colonne `parcel_bank` que `parcel_logs` n'a
        // jamais eue. Elle creait le colis, ratait son journal, rendait « une
        // erreur est survenue » — et laissait le colis derriere elle, faute de
        // transaction. Les deux sont corriges ; le journal le prouve.
        $this->assertSame(1, ParcelLogs::where('parcel_id', Parcel::latest('id')->first()->id)->count());
    }

    /**
     * Dupliquer par le repository marchand, comme le fait l'écran du panneau.
     * La route web n'est pas montée en test (elle dépend du domaine du
     * locataire), on passe donc par le repository lui-même.
     */
    private function dupliquer(Parcel $original): bool
    {
        // Le guard par defaut, pas celui de Sanctum : le repository lit
        // `auth()->user()` comme le fait l'ecran web.
        $this->actingAs($this->merchant->user->fresh());

        $requete = new Request([
            'parcel_id' => $original->id,
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('company_id', Merchant::firstOrFail()->company_id)->where('code', DeliveryZone::COTONOU)->value('id'),
            'cash_collection' => 50000,
            'weight' => 1,
            'shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => $original->customer_name,
            'customer_phone' => $original->customer_phone,
            'customer_address' => $original->customer_address,
            'pickup_phone' => $original->pickup_phone,
            'pickup_address' => $original->pickup_address,
        ]);

        return app(MerchantParcelInterface::class)->duplicateStore($requete, $this->merchant->id);
    }
}
