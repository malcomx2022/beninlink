<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Merchant_panel\PaymentMethod;
use App\Http\Resources\v10\InvoiceDetailsResource;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\DeliverymanStatement;
use App\Enums\StatementType;
use App\Models\MerchantPayment;
use App\Repositories\Invoice\InvoiceInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\Invoicing\SettlementStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/** Régressions relevées sur les routes réellement consommées par les applications. */
class ThreeApplicationsAlignmentTest extends TestCase
{
    use RefreshDatabase, SeedsTenant, BuildsAccountingFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => 'lot2-test']);
        $this->actingAs(Merchant::firstOrFail()->user);
    }

    public function test_new_invoice_detail_is_readable(): void
    {
        $merchant = Merchant::firstOrFail();
        $merchant->payment_period = 0;
        $merchant->save();
        $this->colisConfie($merchant, $this->livreur(), 'LOT2-NEW', ['status' => ParcelStatus::DELIVERED]);
        $invoice = app(InvoiceInterface::class)->store($merchant->id);
        $this->assertNotNull($invoice);
        Sanctum::actingAs($merchant->user, ['merchant']);
        $this->getJson('/api/v10/invoice-details/'.$invoice->id, ['apiKey' => 'lot2-test'])->assertOk();
    }

    public function test_legacy_invoice_detail_matches_frozen_statement(): void
    {
        $merchant = Merchant::firstOrFail();
        $merchant->payment_period = 0;
        $merchant->save();
        $parcel = $this->colisConfie($merchant, $this->livreur(), 'LOT2-INVOICE', ['status' => ParcelStatus::DELIVERED]);
        $invoice = app(InvoiceInterface::class)->store($merchant->id);
        $this->assertNotNull($invoice);
        // Reproduit la forme historique, où ce champ était encore renseigné.
        $invoice->parcels_id = [$parcel->id];
        $invoice->save();
        $official = SettlementStatement::for($invoice);
        $detail = (new InvoiceDetailsResource($invoice))->resolve();
        $this->assertEquals($official['totals']['net'], $detail['payable_amount']);
    }

    public function test_partial_delivery_records_integer_xof_vat(): void
    {
        $parcel = $this->colisConfie(Merchant::firstOrFail(), $this->livreur(), 'LOT2-PARTIAL');
        $this->assertTrue(app(ParcelInterface::class)->parcelPartialDelivered($parcel->id, new Request(['cash_collection' => 12000])));
        $this->assertEquals(202, (float) $parcel->fresh()->vat_amount);
    }

    public function test_fractional_cod_is_rounded_and_cancel_reverses_the_recorded_amounts(): void
    {
        $merchant = Merchant::firstOrFail();
        $merchant->current_balance = 0;
        $merchant->save();
        $driver = $this->livreur();
        $parcel = $this->colisConfie($merchant, $driver, 'LOT3-ROUND-CANCEL', [
            'cod_charge' => 2.5, 'cod_amount' => 500, 'total_delivery_amount' => 1500,
            'vat_amount' => 270, 'current_payable' => 18230,
        ]);
        $repository = app(ParcelInterface::class);
        $this->assertTrue($repository->parcelPartialDelivered($parcel->id, new Request(['cash_collection' => 17350])));
        $partial = $parcel->fresh();
        $this->assertEquals(434, $partial->cod_amount);
        $this->assertEquals(258, $partial->vat_amount);
        $this->assertEquals(15658, $merchant->fresh()->current_balance);
        $this->assertTrue($repository->parcelPartialDeliveredCancel($parcel->id, new Request()));
        $this->assertEquals(0, $merchant->fresh()->current_balance);
        $this->assertEquals(0, $driver->fresh()->current_balance);
        $this->assertEquals(500, $parcel->fresh()->cod_amount);
        $this->assertEquals(270, $parcel->fresh()->vat_amount);
    }

    public function test_issued_detail_keeps_its_amounts_when_a_parcel_changes(): void
    {
        $merchant = Merchant::firstOrFail();
        $merchant->payment_period = 0;
        $merchant->save();
        $parcel = $this->colisConfie($merchant, $this->livreur(), 'LOT3-FROZEN', [
            'status' => ParcelStatus::DELIVERED,
            'packaging_amount' => 500,
            'total_delivery_amount' => 1700,
            'vat_amount' => 306,
            'current_payable' => 17994,
        ]);
        $invoice = app(InvoiceInterface::class)->store($merchant->id);
        $parcel->forceFill(['cash_collection' => 99, 'delivery_charge' => 80, 'vat_amount' => 0, 'status' => ParcelStatus::RETURN_TO_COURIER])->save();
        Sanctum::actingAs($merchant->user, ['merchant']);
        $this->getJson('/api/v10/invoice-details/'.$invoice->id, ['apiKey' => 'lot2-test'])
            ->assertOk()->assertJsonPath('data.total_deliverd_amount', 20000)
            ->assertJsonPath('data.vat_amount', 306)
            ->assertJsonPath('data.other_fees', 500)
            ->assertJsonPath('data.payable_amount', 17994)
            ->assertJsonPath('data.statement_consistent', true);
    }

    /** @dataProvider invalidCollections */
    public function test_both_driver_routes_reject_invalid_collection_without_writing($amount, bool $generic): void
    {
        $driver = $this->livreur();
        $parcel = $this->colisConfie(Merchant::firstOrFail(), $driver, 'LOT3-INVALID');
        Sanctum::actingAs($driver->user, ['deliveryman']);
        $path = $generic ? '/api/v10/deliveryman/parcel-status-update' : '/api/v10/deliveryman/parcel/partial-delivered/'.$parcel->id;
        $this->postJson($path, [
            'parcel_id' => $parcel->id,
            'status_action' => ParcelStatus::PARTIAL_DELIVERED,
            'cash_collection' => $amount,
        ], ['apiKey' => 'lot2-test'])->assertStatus(422);
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, (int) $parcel->fresh()->status);
        $this->assertSame(0, MerchantStatement::count());
    }

    public static function invalidCollections(): array
    {
        $cases = [];
        foreach ([null, '', -1, 100.5, 'incorrect'] as $amount) {
            foreach ([false, true] as $generic) {
                $cases[] = [$amount, $generic];
            }
        }
        return $cases;
    }

    public function test_explicit_zero_collection_is_allowed(): void
    {
        $driver = $this->livreur();
        $parcel = $this->colisConfie(Merchant::firstOrFail(), $driver, 'LOT3-ZERO');
        Sanctum::actingAs($driver->user, ['deliveryman']);
        $this->postJson('/api/v10/deliveryman/parcel-status-update', [
            'parcel_id' => $parcel->id, 'status_action' => ParcelStatus::PARTIAL_DELIVERED, 'cash_collection' => 0,
        ], ['apiKey' => 'lot2-test'])->assertOk();
        $this->assertEquals(0, $parcel->fresh()->cash_collection);
    }

    public function test_driver_expenses_are_not_the_income_total(): void
    {
        $driver = $this->livreur();
        foreach ([StatementType::INCOME => 1000, StatementType::EXPENSE => 300] as $type => $amount) {
            DeliverymanStatement::forceCreate([
                'company_id' => $driver->company_id, 'delivery_man_id' => $driver->id,
                'type' => $type, 'amount' => $amount, 'date' => now(),
            ]);
        }
        Sanctum::actingAs($driver->user, ['deliveryman']);
        $response = $this->getJson('/api/v10/deliveryman/income-expense', ['apiKey' => 'lot2-test'])->assertOk();
        $this->assertSame(amountValue(300), $response->json('data.deliveryInfo.totalDeliveryExpense'));
    }

    public function test_actual_driver_route_rejects_negative_collection(): void
    {
        $driver = $this->livreur();
        $parcel = $this->colisConfie(Merchant::firstOrFail(), $driver, 'LOT2-DRIVER');
        Sanctum::actingAs($driver->user, ['deliveryman']);
        $this->postJson('/api/v10/deliveryman/parcel-status-update', [
            'parcel_id' => $parcel->id,
            'status_action' => ParcelStatus::PARTIAL_DELIVERED,
            'cash_collection' => -100,
        ], ['apiKey' => 'lot2-test'])->assertStatus(422);
    }

    private function requestWithdrawal($amount): void
    {
        $merchant = Merchant::firstOrFail();
        $merchant->current_balance = 100000;
        $merchant->save();
        $account = MerchantPayment::forceCreate([
            'merchant_id' => $merchant->id,
            'payment_method' => PaymentMethod::mobile,
            'holder_name' => 'Test lot 2',
            'mobile_company' => 'MTN MoMo',
            'mobile_no' => '22997000001',
            'account_type' => 'Personnel',
        ]);
        Sanctum::actingAs($merchant->user, ['merchant']);
        $this->postJson('/api/v10/payment-request/store', [
            'amount' => $amount,
            'merchant_account' => $account->id,
        ], ['apiKey' => 'lot2-test'])->assertStatus(422);
    }

    public function test_withdrawal_rejects_negative_amount(): void
    {
        $this->requestWithdrawal(-100);
    }

    public function test_withdrawal_rejects_fractional_xof(): void
    {
        $this->requestWithdrawal(100.5);
    }

    public function test_withdrawal_rejects_zero(): void
    {
        $this->requestWithdrawal(0);
    }
}
