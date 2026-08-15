<?php

namespace Tests\Feature;

use App\Services\Payments\FedaPayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/** Valide NOTRE logique (idempotence, crédit unique, rejet signature), pas le SDK. */
class FedaPayWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function seedPending(string $ref): void
    {
        DB::table('fedapay_transactions')->insert([
            'reference' => $ref, 'tenant_id' => 1, 'user_id' => 1, 'amount' => 2000,
            'purpose' => 'wallet_recharge', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_signature_invalide_est_rejetee(): void
    {
        $this->mock(FedaPayGateway::class, fn ($m) => $m->shouldReceive('verifyWebhook')->once()->andReturnNull());
        $this->postJson('/fedapay/webhook', ['event' => 'transaction.approved'])->assertStatus(400);
    }

    public function test_paiement_approuve_credite_une_seule_fois(): void
    {
        $ref = 'BLK-TESTREF001';
        $this->seedPending($ref);
        $event = (object) ['name' => 'transaction.approved',
            'entity' => (object) ['custom_metadata' => (object) ['reference' => $ref]]];
        $this->mock(FedaPayGateway::class, fn ($m) => $m->shouldReceive('verifyWebhook')->andReturn($event));

        $this->postJson('/fedapay/webhook', [])->assertStatus(200);
        $this->postJson('/fedapay/webhook', [])->assertStatus(200); // rejeu

        $this->assertDatabaseHas('fedapay_transactions', ['reference' => $ref, 'status' => 'paid']);
        $this->assertSame(1, DB::table('fedapay_transactions')->where('reference', $ref)->where('status', 'paid')->count());
    }

    protected function tearDown(): void { Mockery::close(); parent::tearDown(); }
}
