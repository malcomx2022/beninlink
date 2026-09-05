<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * W5 — la création d'un colis et le débit du portefeuille sont **atomiques**.
 *
 * Le débit était enveloppé dans un `try { … } catch { }` **vide** : un échec
 * laissait le colis créé et le marchand non facturé, sans trace. La trace a
 * d'abord été ajoutée ; la décision du 2026-09-05 va jusqu'au bout et lie les
 * deux écritures.
 *
 * Le prix est une disponibilité : si le portefeuille est indisponible, le colis
 * n'est pas créé et le marchand réessaie. C'est le bon sens du métier — un colis
 * qu'on ne sait pas facturer ne doit pas partir — et l'annulation est sûre, tout
 * ce que la création déclenche s'écrivant en base.
 */
class ParcelWalletDebitAtomicityTest extends TestCase
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
        // Le débit n'a lieu que si le marchand règle par portefeuille.
        $this->merchant->wallet_use_activation = Status::ACTIVE;
        $this->merchant->wallet_balance = 100000;
        $this->merchant->save();
    }

    private function creerUnColis()
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        return $this->postJson('/api/v10/parcel/store', [
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'weight' => 1,
            'shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000031',
            'customer_address' => 'Cotonou, Akpakpa',
        ], ['apiKey' => self::API_KEY]);
    }

    /** Le chemin nominal : le colis est créé et le portefeuille débité. */
    public function test_a_successful_debit_leaves_no_error(): void
    {
        Log::spy();

        $this->creerUnColis()->assertOk();

        $this->assertSame(1, Parcel::count());
        $this->assertLessThan(100000, (float) Merchant::find($this->merchant->id)->wallet_balance);

        Log::shouldNotHaveReceived('error');
    }

    /** Le débit échoue : rien n'est créé, et l'échec laisse une trace. */
    public function test_a_failed_debit_rolls_the_whole_creation_back(): void
    {
        Log::spy();

        $this->app->bind(WalletInterface::class, fn () => $this->portefeuilleEnPanne());


        // Le socle rend 500 quand `store()` échoue : sa convention, inchangée.
        $this->creerUnColis()->assertStatus(500);

        // Rien n'a été écrit : ni le colis, ni un solde entamé.
        $this->assertSame(0, Parcel::count());
        $this->assertSame(100000.0, (float) Merchant::find($this->merchant->id)->wallet_balance);

        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $contexte) {
                return str_contains((string) $message, 'Creation de colis annulee')
                    && (int) $contexte['merchant_id'] === (int) $this->merchant->id
                    && str_contains((string) $contexte['message'], 'base indisponible');
            })
            ->once();
    }

    /**
     * L'alerte douanière que la création déclenche disparaît elle aussi : c'est
     * ce qui rend l'annulation sûre, tout s'écrivant en base.
     */
    public function test_nothing_the_creation_triggers_survives_the_rollback(): void
    {
        $this->app->bind(WalletInterface::class, fn () => $this->portefeuilleEnPanne());

        $this->creerUnColis()->assertStatus(500);

        $this->assertSame(0, Parcel::count());
        $this->assertSame(0, \App\Models\Backend\CustomsAlert::count());
    }

    /** Le service de portefeuille tombe en panne au moment du débit. */
    private function portefeuilleEnPanne(): WalletInterface
    {
        return new class implements WalletInterface {
            public function get($request = null) {}
            public function recharges($request = null) {}
            public function getFind($id) {}
            public function store($request) {}
            public function paymentStatus($orderId, $transactionId, $status) {}
            public function approved($id) {}
            public function rejected($id) {}
            public function expense($request)
            {
                throw new \RuntimeException('base indisponible');
            }
            public function adminstore($request) {}
            public function delete($id) {}
        };
    }
}
