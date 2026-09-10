<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Services\Invoicing\InvoiceNumbering;
use App\Models\MerchantShops;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * `invoice:generate` doit servir **toutes** les sociétés, pas seulement la première.
 *
 * La commande bouclait sur les marchands de `settings()->id`. Hors requête
 * HTTP — et une tâche planifiée n'en a pas — `settings()` retombe sur la
 * société 1 : les relevés des autres transporteurs n'étaient donc jamais
 * générés, sans erreur ni alerte.
 *
 * Le marchand du jeu de test appartient à la **société 2** (`MerchantSeeder`),
 * ce qui rend la régression directement observable : avant le correctif, ce
 * test ne trouve aucun relevé.
 */
class InvoiceGenerateTenantTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->assertSame(2, (int) $this->marchand->company_id, 'le marchand du jeu de test doit être hors société 1');
    }

    private function colisLivre(string $tracking = 'BL-FAC-1'): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha K.',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'total_delivery_amount' => 1500,
            'vat_amount' => 270,
            'current_payable' => 48230,
            'tracking_id' => $tracking,
            'status' => ParcelStatus::DELIVERED,
        ])->save();

        return $parcel;
    }

    public function test_le_passage_planifie_emet_le_releve_d_une_societe_autre_que_la_premiere(): void
    {
        $parcel = $this->colisLivre();

        $this->artisan('invoice:generate')->assertSuccessful();

        $releve = Invoice::where('merchant_id', $this->marchand->id)->first();

        $this->assertNotNull($releve, 'aucun relevé émis : la commande n\'a pas vu la société du marchand');
        $this->assertSame(2, (int) $releve->company_id, 'le relevé doit porter la société du marchand');
        $this->assertSame($releve->id, (int) $parcel->fresh()->invoice_id, 'le colis doit être rattaché au relevé');
    }

    public function test_le_numero_du_releve_suit_la_societe_du_marchand(): void
    {
        $this->colisLivre();

        // Même règle que le service : le préfixe de LA société, en majuscules.
        $prefixe = app(InvoiceNumbering::class)->prefixFor(2);

        $this->artisan('invoice:generate')->assertSuccessful();

        $releve = Invoice::where('merchant_id', $this->marchand->id)->firstOrFail();

        $this->assertStringStartsWith($prefixe . '-', $releve->invoice_id);
        $this->assertSame(2, (int) $releve->company_id);
        $this->assertSame(1, (int) $releve->sequence, 'première séquence de l\'exercice, pour CETTE société');
    }

    public function test_l_option_societe_limite_la_portee(): void
    {
        $this->colisLivre();

        // La société 1 n'a pas ce marchand : rien ne doit sortir.
        $this->artisan('invoice:generate', ['--societe' => 1])->assertSuccessful();
        $this->assertSame(0, Invoice::where('merchant_id', $this->marchand->id)->count());

        // La sienne, en revanche, le sert.
        $this->artisan('invoice:generate', ['--societe' => 2])->assertSuccessful();
        $this->assertSame(1, Invoice::where('merchant_id', $this->marchand->id)->count());
    }

    public function test_le_depot_scope_refuse_un_marchand_d_une_autre_societe(): void
    {
        // Le garde de la route « générer le relevé de ce marchand » : le dépôt
        // ne rend un marchand que dans la société courante. Hors requête,
        // `settings()` vaut 1 — donc le marchand de la société 2 est invisible,
        // et le contrôleur renvoie 404 au lieu d'émettre son relevé.
        $this->assertNull(
            app(MerchantInterface::class)->get($this->marchand->id),
            'le dépôt ne doit pas rendre un marchand hors de la société courante'
        );
    }
}
