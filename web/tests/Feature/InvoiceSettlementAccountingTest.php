<?php

namespace Tests\Feature;

use App\Enums\BooleanStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Parcel;
use App\Repositories\Invoice\InvoiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le relevé de règlement — ce que le transporteur doit au marchand sur une
 * période, colis par colis.
 *
 * Il ne bouge aucun solde : il **arrête un compte**. Il ramasse les colis
 * livrés (et les retours) qui ne sont encore sur aucun relevé, les totalise, et
 * les marque comme facturés pour qu'ils n'y reviennent jamais.
 *
 * D'où les deux propriétés qui comptent ici, et que ce fichier fixe : rien
 * n'est compté deux fois, et rien n'est compté qui appartienne à quelqu'un
 * d'autre.
 */
class InvoiceSettlementAccountingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        // Sans période de règlement, le relevé est émissible aujourd'hui.
        $this->marchand->payment_period = 0;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());
    }

    private function colisLivre(Merchant $marchand, string $suivi, array $surcharges = []): Parcel
    {
        $colis = new Parcel();
        $colis->forceFill(array_merge([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'customer_name' => 'Client',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => self::CASH,
            'delivery_charge' => 1000,
            'cod_amount' => 200,
            'vat' => 18,
            'vat_amount' => self::VAT,
            'total_delivery_amount' => self::CHARGES,
            'current_payable' => self::PAYABLE,
            'tracking_id' => $suivi,
            'status' => ParcelStatus::DELIVERED,
        ], $surcharges))->save();

        return $colis->fresh();
    }

    private function emettre(?Merchant $marchand = null)
    {
        return app(InvoiceInterface::class)->store(($marchand ?? $this->marchand)->id);
    }

    // ---- ce que le relevé ramasse ----------------------------------------

    /** Les colis livrés non encore facturés, et eux seuls. */
    public function test_it_gathers_the_delivered_parcels_not_yet_invoiced(): void
    {
        $this->colisLivre($this->marchand, 'BL-1');
        $this->colisLivre($this->marchand, 'BL-2');
        // Encore en attente : il n'a rien à faire sur un relevé de règlement.
        $this->colisLivre($this->marchand, 'BL-EN-COURS', ['status' => ParcelStatus::PENDING]);

        $releve = $this->emettre();

        $this->assertNotNull($releve);
        $this->assertSame(2, InvoiceParcel::where('invoice_id', $releve->id)->count());
        $this->assertSame(2 * self::CASH, (float) $releve->cash_collection);
        $this->assertSame(2 * (self::CHARGES + self::VAT), (float) $releve->total_charge);
        $this->assertSame(2 * self::PAYABLE, (float) $releve->current_payable);
    }

    /**
     * Un colis facturé porte la marque de son relevé. C'est cette marque, et
     * elle seule, qui l'empêche d'être payé deux fois.
     */
    public function test_an_invoiced_parcel_is_stamped_and_never_returns(): void
    {
        $colis = $this->colisLivre($this->marchand, 'BL-1');

        $premier = $this->emettre();
        $this->assertSame($premier->id, (int) Parcel::find($colis->id)->invoice_id);

        // Un relevé le lendemain ne doit plus rien trouver.
        Invoice::query()->update(['created_at' => now()->subDays(2)]);
        $this->assertNull($this->emettre());
    }

    /**
     * Le relevé d'un marchand ne ramasse pas les colis d'un autre — y compris
     * d'un autre marchand de la **même** société, où un filtre par société
     * seul ne suffirait pas.
     */
    public function test_it_never_gathers_another_merchants_parcels(): void
    {
        $voisin = $this->voisin();
        $this->colisLivre($this->marchand, 'BL-MOI');
        $this->colisLivre($voisin, 'BL-VOISIN');

        $releve = $this->emettre();

        $this->assertSame(1, InvoiceParcel::where('invoice_id', $releve->id)->count());
        $this->assertSame(self::CASH, (float) $releve->cash_collection);
        $this->assertNull(Parcel::where('tracking_id', 'BL-VOISIN')->firstOrFail()->invoice_id);
    }

    /** Ni ceux d'un autre transporteur. */
    public function test_it_never_gathers_another_companys_parcels(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $this->colisLivre($this->marchand, 'BL-MOI');
        $this->colisLivre($ailleurs, 'BL-AILLEURS');

        $releve = $this->emettre();

        $this->assertSame(1, InvoiceParcel::where('invoice_id', $releve->id)->count());
        $this->assertNull(Parcel::where('tracking_id', 'BL-AILLEURS')->firstOrFail()->invoice_id);
    }

    /**
     * Un retour ne rapporte rien au marchand : il lui **coûte** les frais de
     * retour. Il entre donc au relevé en négatif.
     */
    public function test_a_returned_parcel_costs_the_merchant_its_return_charge(): void
    {
        $this->colisLivre($this->marchand, 'BL-RETOUR', [
            'status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
            'return_charges' => 500,
            'current_payable' => 0,
            'cash_collection' => 0,
        ]);

        $releve = $this->emettre();

        $ligne = InvoiceParcel::where('invoice_id', $releve->id)->firstOrFail();
        $this->assertSame(500.0, (float) $ligne->return_charge);
        $this->assertSame(-500.0, (float) $ligne->current_payable);
        $this->assertSame(-500.0, (float) $releve->current_payable);
    }

    /** Deux relevés le même jour : le second n'est pas émis. */
    public function test_a_second_statement_the_same_day_is_refused(): void
    {
        $this->colisLivre($this->marchand, 'BL-1');
        $this->assertNotNull($this->emettre());

        $this->colisLivre($this->marchand, 'BL-2');
        $this->assertNull($this->emettre());

        $this->assertSame(1, Invoice::count());
        $this->assertNull(Parcel::where('tracking_id', 'BL-2')->firstOrFail()->invoice_id);
    }

    /** Sans colis à régler, pas de relevé vide. */
    public function test_no_statement_is_issued_when_there_is_nothing_to_settle(): void
    {
        $this->assertNull($this->emettre());
        $this->assertSame(0, Invoice::count());
    }

    // ---- le règlement du relevé ------------------------------------------

    /** Marquer un relevé payé, c'est le geste qui clôt la période. */
    public function test_a_statement_can_be_marked_paid(): void
    {
        $this->colisLivre($this->marchand, 'BL-1');
        $releve = $this->emettre();

        // Un relevé neuf est « en traitement » ; le régler le passe à « payé ».
        // On relit en base : l'objet rendu par `store()` ne porte pas encore la
        // valeur par défaut de la colonne.
        $this->assertSame(InvoiceStatus::PROCESSING, (int) Invoice::find($releve->id)->status);

        $ok = app(InvoiceInterface::class)->statusUpdate(new Request([
            'id' => $releve->id,
            'invoice_id' => $releve->invoice_id,
            'status' => InvoiceStatus::PAID,
        ]), $this->marchand->id);

        $this->assertTrue($ok);
        $this->assertSame(InvoiceStatus::PAID, (int) Invoice::find($releve->id)->status);
    }

    /**
     * Mais pas celui d'un autre transporteur. Le socle lisait `Invoice::where()`
     * sans scope : un administrateur qui connaissait le triplet marquait payé
     * le relevé d'une autre société — sans que le marchand concerné ait rien
     * reçu.
     */
    public function test_a_statement_of_another_company_cannot_be_marked_paid(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $ailleurs->payment_period = 0;
        $ailleurs->save();
        $this->colisLivre($ailleurs, 'BL-AILLEURS');

        // Le relevé du voisin est émis dans SON contexte de société.
        $this->actingAs($ailleurs->user->fresh());
        $releveAilleurs = $this->emettre($ailleurs);
        $this->assertNotNull($releveAilleurs);

        // De retour chez nous, il doit rester hors de portée.
        $this->actingAs($this->marchand->user->fresh());
        $refuse = app(InvoiceInterface::class)->statusUpdate(new Request([
            'id' => $releveAilleurs->id,
            'invoice_id' => $releveAilleurs->invoice_id,
            'status' => InvoiceStatus::PAID,
        ]), $ailleurs->id);

        $this->assertFalse($refuse);
        $this->assertSame(InvoiceStatus::PROCESSING, (int) Invoice::find($releveAilleurs->id)->status);
    }

    private function voisin(): Merchant
    {
        $user = $this->marchand->user->replicate();
        $user->email = 'voisin@example.test';
        $user->mobile = '0022997000009';
        $user->unique_id = 'U-VOISIN';
        $user->save();

        $voisin = $this->marchand->replicate();
        $voisin->user_id = $user->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->save();

        return $voisin->fresh();
    }
}
