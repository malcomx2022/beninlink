<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
use App\Models\MerchantShops;
use App\Services\Parcel\WalletDebit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * `beninlink:colis-non-debites` — les colis que personne n'a jamais facturés.
 *
 * Troisième question de l'import : que fait-on du passé ? Les correctifs (W5,
 * le `catch` vide, l'import Excel) ne rattrapent rien de ce qui est déjà en
 * base. Pour un marchand au portefeuille, le débit à la création *est* la
 * facturation : ces colis sont des créances invisibles.
 *
 * La commande **constate** par défaut. `--regulariser` écrit, sans plancher de
 * solde — la dette est acquise, refuser la laisserait invisible — et refuse en
 * production sans `--force`.
 */
class UndebitedParcelsCommandTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        $this->merchant->wallet_use_activation = Status::ACTIVE;
        $this->merchant->wallet_balance = 10000;
        $this->merchant->save();
    }

    private function colis(Merchant $marchand, string $suivi, float $frais = 2500): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Porto-Novo',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 20000,
            'total_delivery_amount' => $frais,
            'current_payable' => 20000 - $frais,
            'tracking_id' => $suivi,
            'status' => \App\Enums\ParcelStatus::PENDING,
        ])->save();

        return $parcel->fresh();
    }

    private function soldeActuel(?Merchant $marchand = null): float
    {
        return (float) Merchant::find(($marchand ?? $this->merchant)->id)->wallet_balance;
    }

    /** Le constat : le colis est listé, et rien n'est écrit. */
    public function test_it_reports_a_parcel_that_was_never_debited(): void
    {
        $this->colis($this->merchant, 'BL-NONDEBITE');

        $this->artisan('beninlink:colis-non-debites')
            ->expectsOutputToContain('1 colis jamais facturés')
            ->assertExitCode(0);

        $this->assertSame(0, Wallet::count());
        $this->assertSame(10000.0, $this->soldeActuel());
    }

    /** Un colis qui porte son écriture n'est pas un impayé. */
    public function test_a_debited_parcel_is_not_reported(): void
    {
        $colis = $this->colis($this->merchant, 'BL-DEBITE');
        app(WalletDebit::class)->apply($colis);

        $this->artisan('beninlink:colis-non-debites')
            ->expectsOutputToContain('à jour')
            ->assertExitCode(0);
    }

    /**
     * Un marchand qui règle au relevé n'a jamais eu à être débité : ses colis
     * ne sont pas des impayés.
     */
    public function test_a_merchant_outside_the_wallet_is_ignored(): void
    {
        $this->merchant->wallet_use_activation = Status::INACTIVE;
        $this->merchant->save();
        $this->colis($this->merchant, 'BL-HORS-PORTEFEUILLE');

        $this->artisan('beninlink:colis-non-debites')
            ->expectsOutputToContain('Aucun marchand ne règle par portefeuille')
            ->assertExitCode(0);
    }

    /** `--regulariser` écrit les débits manquants, et une seule fois. */
    public function test_regularising_writes_the_missing_debits_once(): void
    {
        $this->colis($this->merchant, 'BL-A');
        $this->colis($this->merchant, 'BL-B');

        $this->artisan('beninlink:colis-non-debites --regulariser')
            ->expectsOutputToContain('2 débit(s) écrit(s)')
            ->assertExitCode(0);

        $this->assertSame(2, Wallet::count());
        $this->assertSame(5000.0, $this->soldeActuel());

        // Relancer ne redébite pas : le rapprochement se fait sur le libellé.
        $this->artisan('beninlink:colis-non-debites --regulariser')->assertExitCode(0);

        $this->assertSame(2, Wallet::count());
        $this->assertSame(5000.0, $this->soldeActuel());
    }

    /**
     * L'écriture porte la société du **marchand**, pas celle sur laquelle
     * `settings()` retombe en console — sans quoi la créance atterrirait chez
     * un autre transporteur.
     */
    public function test_the_written_entry_belongs_to_the_merchants_company(): void
    {
        $this->colis($this->merchant, 'BL-SOCIETE');

        $this->artisan('beninlink:colis-non-debites --regulariser')->assertExitCode(0);

        $this->assertSame($this->merchant->company_id, Wallet::firstOrFail()->company_id);
    }

    /**
     * La régularisation ignore le plancher de solde, et c'est délibéré : le
     * colis existe, la dette est acquise. La refuser laisserait la créance
     * invisible — l'état même qu'on corrige.
     */
    public function test_regularising_may_take_the_balance_below_zero(): void
    {
        $this->merchant->wallet_balance = 1000;
        $this->merchant->save();
        $this->colis($this->merchant, 'BL-DECOUVERT', 2500);

        $this->artisan('beninlink:colis-non-debites --regulariser')->assertExitCode(0);

        $this->assertSame(-1500.0, $this->soldeActuel());
    }

    /** `--marchand=` limite la portée : on régularise un compte à la fois. */
    public function test_it_can_be_limited_to_one_merchant(): void
    {
        $voisin = $this->voisin();
        $this->colis($this->merchant, 'BL-MOI');
        $this->colis($voisin, 'BL-VOISIN');

        $this->artisan('beninlink:colis-non-debites --regulariser --marchand=' . $this->merchant->id)
            ->expectsOutputToContain('1 débit(s) écrit(s)')
            ->assertExitCode(0);

        $this->assertSame(1, Wallet::count());
        $this->assertSame(10000.0, $this->soldeActuel($voisin));
    }

    private function voisin(): Merchant
    {
        $user = $this->merchant->user->replicate();
        $user->email = 'voisin@example.test';
        $user->mobile = '0022997000009';
        $user->unique_id = 'U-VOISIN';
        $user->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $user->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->wallet_use_activation = Status::ACTIVE;
        $voisin->wallet_balance = 10000;
        $voisin->save();

        return $voisin->fresh();
    }
}
