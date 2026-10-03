<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\VatStatement;
use App\Repositories\Invoice\InvoiceInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\Invoicing\SettlementStatement;
use App\Services\Invoicing\SyscohadaJournal;
use App\Services\Parcel\ReturnVat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D2, question 6** (S73) — le frais de retour est une prestation **taxable**.
 *
 * Le socle forçait `vat_amount = 0` sur un colis retourné : le retour était
 * hors champ sans que personne l'ait décidé. Le porteur a tranché le
 * 2026-10-03. Ce fichier fixe les trois moments où la décision s'applique —
 * le retour (prélèvement), son annulation (restitution), le relevé
 * (facturation) — et la commande de **constat** des relevés anciens, qui ne
 * corrige rien : un relevé émis ne se modifie pas (D8, D9).
 */
class ReturnVatTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    private Merchant $marchand;
    private DeliveryMan $livreur;
    private Parcel $colis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->current_balance = 0;
        $this->marchand->return_charges = 50; // 50 % du tarif de livraison (1 000 F) = 500 F HT
        $this->marchand->payment_period = 0;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());

        $this->livreur = $this->livreur();
        $this->livreur->return_charge = 400;
        $this->livreur->save();

        $this->colis = $this->colisConfie($this->marchand, $this->livreur, 'BL-RETOUR-TVA');
        $this->colis->status = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
        $this->colis->save();
    }

    private function retourRecu(): bool
    {
        return app(ParcelInterface::class)->returnReceivedByMerchant($this->colis->id, new Request());
    }

    private function annulerRetour(): bool
    {
        return app(ParcelInterface::class)->returnReceivedByMerchantCancel($this->colis->id, new Request());
    }

    private function soldeMarchand(): float
    {
        return (float) Merchant::find($this->marchand->id)->current_balance;
    }

    // ---- le retour ----------------------------------------------------------

    public function test_le_retour_preleve_le_frais_et_sa_tva_au_franc(): void
    {
        $this->assertTrue($this->retourRecu());

        $colis = Parcel::find($this->colis->id);
        $this->assertSame(500.0, (float) $colis->return_charges, 'le frais HT');
        $this->assertSame(90.0, (float) $colis->return_vat_amount, '18 % de 500 F, au franc');
        $this->assertSame(-590.0, $this->soldeMarchand(), 'le marchand paie le retour TTC');

        // Deux lignes, comme la livraison écrit sa TVA à part : le frais, et la TVA.
        $tva = MerchantStatement::where('parcel_id', $colis->id)
            ->where('note', __('statementNote.return_vat_merchant_statement'))->get();
        $this->assertCount(1, $tva);
        $this->assertSame(StatementType::EXPENSE, (int) $tva->first()->type);
        $this->assertSame(90.0, (float) $tva->first()->amount);

        // ... et la TVA collectée du transporteur en garde la trace.
        $this->assertSame(90.0, (float) VatStatement::where('parcel_id', $colis->id)->where('type', StatementType::INCOME)->sum('amount'));
    }

    /** Le taux est celui du colis ; un colis sans taux propre prend celui de la société (D1). */
    public function test_un_colis_sans_taux_propre_prend_le_taux_de_la_societe(): void
    {
        $colis = Parcel::find($this->colis->id);
        $colis->vat = 0;
        $colis->save();

        $this->assertSame(18.0, ReturnVat::taux($colis->fresh(), $this->marchand->fresh()));
        $this->assertSame(90, ReturnVat::montant($colis->fresh(), $this->marchand->fresh(), 500));
        // Au franc le plus proche, comme toute TVA depuis le 2026-09-18 (D2 q.7).
        $this->assertSame(302, ReturnVat::arrondi(1680, 18));
    }

    public function test_l_annulation_rend_le_frais_et_sa_tva_et_les_efface_du_colis(): void
    {
        $this->retourRecu();
        $this->assertTrue($this->annulerRetour());

        $this->assertSame(0.0, $this->soldeMarchand());
        $colis = Parcel::find($this->colis->id);
        $this->assertSame(0.0, (float) $colis->return_charges);
        $this->assertSame(0.0, (float) $colis->return_vat_amount, 'sans quoi le prochain relevé facturerait la TVA d’un retour annulé');

        // On inverse, on n'efface pas : la TVA a sa contrepartie, et la TVA collectée retombe à zéro.
        $this->assertSame(2, MerchantStatement::where('parcel_id', $colis->id)
            ->where('note', __('statementNote.return_vat_merchant_statement'))->count());
        $this->assertSame(
            (float) VatStatement::where('parcel_id', $colis->id)->where('type', StatementType::INCOME)->sum('amount'),
            (float) VatStatement::where('parcel_id', $colis->id)->where('type', StatementType::EXPENSE)->sum('amount'),
        );
    }

    // ---- le relevé ----------------------------------------------------------

    public function test_le_releve_facture_le_retour_ttc_et_le_journal_porte_sa_tva(): void
    {
        $this->retourRecu();

        $releve = app(InvoiceInterface::class)->store($this->marchand->id);
        $this->assertInstanceOf(Invoice::class, $releve);

        $ligne = InvoiceParcel::where('invoice_id', $releve->id)->firstOrFail();
        $this->assertSame(500.0, (float) $ligne->return_charge);
        $this->assertSame(90.0, (float) $ligne->vat_amount, 'plus de TVA forcée à zéro sur un retour');
        $this->assertSame(590.0, (float) $ligne->total_charge_amount);
        $this->assertSame(-590.0, (float) $ligne->current_payable);
        $this->assertSame(-590.0, (float) $releve->current_payable);
        $this->assertSame(590.0, (float) $releve->total_charge);

        $t = SettlementStatement::for($releve->fresh())['totals'];
        $this->assertSame(500, $t['fees_ht']);
        $this->assertSame(90, $t['vat']);
        $this->assertSame(590, $t['fees_ttc']);
        $this->assertTrue($t['consistent'], 'le relevé et sa ligne en base disent la même chose');

        config(['syscohada.auxiliary' => 'collectif']);
        $lignes = SyscohadaJournal::linesFor($releve->fresh());
        $this->assertSame(['4111', '7061', '4431', '4712', '4111'], array_column($lignes, 'compte'));
        $this->assertSame(500, $lignes[1]['credit'], 'le retour est dans le HT des prestations');
        $this->assertSame(90, $lignes[2]['credit'], 'et sa TVA dans la TVA facturée');
    }

    // ---- le constat des relevés anciens ---------------------------------------

    /** Un relevé émis avant S73 : le retour y est facturé sans TVA. */
    private function releveAncien(string $numero = 'CO-2026-000009', int $frais = 500): Invoice
    {
        $colis = Parcel::find($this->colis->id);
        $colis->status = ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
        $colis->return_charges = $frais;
        $colis->return_vat_amount = 0;
        $colis->save();

        $releve = new Invoice();
        $releve->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'invoice_id' => $numero,
            'issued_on' => '2026-09-15',
            'total_charge' => $frais,
            'current_payable' => -$frais,
            'status' => InvoiceStatus::PAID,
        ])->save();

        $ligne = new InvoiceParcel();
        $ligne->forceFill([
            'company_id' => $this->marchand->company_id,
            'invoice_id' => $releve->id,
            'parcel_id' => $colis->id,
            'parcel_status' => ParcelStatus::RETURN_TO_COURIER,
            'return_charge' => $frais,
            'vat_amount' => 0,
            'total_charge_amount' => $frais,
            'current_payable' => -$frais,
        ])->save();

        $colis->invoice_id = $releve->id;
        $colis->save();

        return $releve;
    }

    public function test_la_commande_constate_un_releve_ancien_sans_le_toucher(): void
    {
        $releve = $this->releveAncien();

        $this->artisan('beninlink:retours-sans-tva')
            ->expectsOutputToContain('CO-2026-000009')
            ->expectsOutputToContain('1 relevé(s), 1 colis en retour facturés sans TVA')
            ->assertSuccessful();

        // Constat seul : ni le relevé, ni sa ligne, ni le colis n'ont bougé.
        $this->assertSame(-500.0, (float) Invoice::find($releve->id)->current_payable);
        $this->assertSame(0.0, (float) InvoiceParcel::where('invoice_id', $releve->id)->value('vat_amount'));
        $this->assertSame(0.0, (float) Parcel::find($this->colis->id)->return_vat_amount);
        $this->assertSame(0.0, $this->soldeMarchand());
    }

    /** La TVA manquante est calculée au taux du colis, au franc : 18 % de 500 = 90. */
    public function test_le_constat_chiffre_la_tva_manquante_au_taux_du_colis(): void
    {
        $this->releveAncien('CO-2026-000010', 1680);

        $this->artisan('beninlink:retours-sans-tva', ['--societe' => $this->marchand->company_id])
            ->expectsOutputToContain(formatAmount(302))
            ->assertSuccessful();
    }

    public function test_le_constat_ne_trouve_rien_quand_les_retours_portent_leur_tva(): void
    {
        $this->retourRecu();
        app(InvoiceInterface::class)->store($this->marchand->id);

        $this->artisan('beninlink:retours-sans-tva')
            ->expectsOutputToContain('Aucun relevé émis n’a facturé de frais de retour sans TVA')
            ->assertSuccessful();
    }

    public function test_le_constat_filtre_par_societe_sans_settings(): void
    {
        $this->releveAncien();
        $autre = $this->marchandDUneAutreSociete('T');

        $this->artisan('beninlink:retours-sans-tva', ['--societe' => $autre->company_id])
            ->expectsOutputToContain('Aucun relevé émis')
            ->assertSuccessful();
    }

    /** La commande n'a AUCUNE option d'écriture : la régularisation attend l'expert-comptable. */
    public function test_la_commande_n_a_pas_d_option_de_correction(): void
    {
        $definition = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all()['beninlink:retours-sans-tva']->getDefinition();

        $this->assertFalse($definition->hasOption('corriger'));
        $this->assertFalse($definition->hasOption('force'));
        $this->assertSame(['societe', 'marchand'], array_values(array_diff(array_keys($definition->getOptions()), ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'])));
    }
}
