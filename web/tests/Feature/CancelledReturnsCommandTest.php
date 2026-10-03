<?php

namespace Tests\Feature;

use App\Console\Commands\CancelledReturnsCommand;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * `beninlink:retours-annules` — la reprise du passé sur les retours annulés.
 *
 * Le correctif du 2026-09-07 rend désormais au marchand ce qu'une annulation
 * de retour lui avait prélevé. Il ne rattrape pas ce qui est déjà en base.
 *
 * Ces tests partent donc du **dégât d'origine**, réinjecté tel que l'ancienne
 * annulation le laissait : l'événement supprimé, le statut reculé, et l'argent
 * là où il était. C'est le seul moyen honnête de vérifier une reprise du passé
 * une fois le code corrigé — on ne peut plus produire le dégât en appelant
 * l'étape.
 */
class CancelledReturnsCommandTest extends TestCase
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
        $this->marchand->return_charges = 50;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());

        $this->livreur = $this->livreur();
        $this->livreur->return_charge = 400;
        $this->livreur->save();

        // Depuis S73 (D2 q.6) le retour est taxable : 50 % de 1 000 F = 500 F HT,
        // + 90 F de TVA = 590 F prélevés. Le frais et sa TVA vont ensemble.
        $this->colis = $this->colisConfie($this->marchand, $this->livreur, 'BL-RETOUR-PASSE');
        $this->colis->status = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
        $this->colis->save();
    }

    private function recevoirLeRetour(): bool
    {
        return app(ParcelInterface::class)->returnReceivedByMerchant($this->colis->id, new Request());
    }

    /**
     * L'annulation **telle qu'elle était** avant le 2026-09-07 : l'événement
     * disparaît, le statut recule, et pas un franc ne bouge.
     */
    private function annulerALAncienne(): void
    {
        ParcelEvent::where('parcel_id', $this->colis->id)
            ->where('parcel_status', ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)
            ->delete();

        $colis = Parcel::find($this->colis->id);
        $colis->status = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
        $colis->save();
    }

    private function soldeMarchand(): float
    {
        return (float) Merchant::find($this->marchand->id)->current_balance;
    }

    private function soldeLivreur(): float
    {
        return (float) DeliveryMan::find($this->livreur->id)->current_balance;
    }

    // ---- le constat -------------------------------------------------------

    public function test_un_retour_annule_a_l_ancienne_est_retrouve(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $this->artisan('beninlink:retours-annules')
            ->expectsOutputToContain('BL-RETOUR-PASSE')
            ->expectsOutputToContain('1 colis dont le frais de retour n\'a pas été rendu.')
            ->assertExitCode(0);

        // Le constat n'écrit rien.
        $this->assertSame(-590.0, $this->soldeMarchand());
    }

    public function test_un_retour_qui_tient_encore_n_est_pas_touche(): void
    {
        $this->recevoirLeRetour();

        $this->artisan('beninlink:retours-annules')
            ->expectsOutputToContain('Aucun retour annulé sans réversion')
            ->assertExitCode(0);
    }

    public function test_un_colis_sans_retour_n_interesse_pas_la_commande(): void
    {
        $this->artisan('beninlink:retours-annules')
            ->expectsOutputToContain('Aucun retour annulé sans réversion')
            ->assertExitCode(0);
    }

    // ---- la correction ----------------------------------------------------

    public function test_la_correction_rend_au_marchand_ce_qu_il_a_paye(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $this->assertSame(-590.0, $this->soldeMarchand());

        $this->artisan('beninlink:retours-annules --corriger')->assertExitCode(0);

        $this->assertSame(0.0, $this->soldeMarchand());
        // On inverse, on n'efface pas : les deux lignes coexistent.
        $this->assertSame(2, MerchantStatement::where('parcel_id', $this->colis->id)
            ->where('note', CancelledReturnsCommand::NOTE_MARCHAND)->count());
        $this->assertSame(1, MerchantStatement::where('parcel_id', $this->colis->id)
            ->where('type', StatementType::INCOME)->count());
    }

    public function test_la_correction_reprend_au_livreur_sa_course(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $this->assertSame(400.0, $this->soldeLivreur());

        $this->artisan('beninlink:retours-annules --corriger')->assertExitCode(0);

        $this->assertSame(0.0, $this->soldeLivreur());
        $this->assertSame(1, DeliverymanStatement::where('parcel_id', $this->colis->id)
            ->where('type', StatementType::EXPENSE)->count());
    }

    /**
     * Le second dégât, moins visible que le premier : le relevé rassemble les
     * colis en retour **par statut**, et `RETURN_ASSIGN_TO_MERCHANT` — là où
     * l'annulation les renvoie — en fait partie. Rendre l'argent au solde sans
     * effacer le frais l'aurait repris au relevé suivant.
     */
    public function test_le_frais_residuel_est_efface_du_colis(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $this->assertSame(500.0, (float) Parcel::find($this->colis->id)->return_charges);

        $this->artisan('beninlink:retours-annules --corriger')->assertExitCode(0);

        $this->assertSame(0.0, (float) Parcel::find($this->colis->id)->return_charges);
    }

    public function test_un_second_passage_ne_trouve_plus_rien(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $this->artisan('beninlink:retours-annules --corriger')->assertExitCode(0);

        $this->artisan('beninlink:retours-annules')
            ->expectsOutputToContain('Aucun retour annulé sans réversion')
            ->assertExitCode(0);

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0.0, $this->soldeLivreur());
    }

    /**
     * Le double prélèvement : réception, annulation à l'ancienne, réception à
     * nouveau. Le retour tient bel et bien — un frais lui est dû. C'est le
     * second qui doit revenir, pas les deux.
     */
    public function test_un_double_prelevement_ne_rend_que_le_second(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();
        $this->recevoirLeRetour();

        $this->assertSame(-1180.0, $this->soldeMarchand());
        $this->assertSame(800.0, $this->soldeLivreur());

        $this->artisan('beninlink:retours-annules --corriger')->assertExitCode(0);

        $this->assertSame(-590.0, $this->soldeMarchand(), 'un retour tient : un frais reste dû');
        $this->assertSame(400.0, $this->soldeLivreur());
        // Le retour tient toujours : son frais ne doit pas être effacé.
        $this->assertSame(500.0, (float) Parcel::find($this->colis->id)->return_charges);
    }

    /**
     * La commande tourne hors requête, donc hors société : `settings()` y
     * retomberait sur la première entreprise venue. Le filtre est donc
     * explicite, et il doit vraiment filtrer.
     */
    public function test_le_filtre_par_societe_laisse_les_autres_tranquilles(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $autre = $this->marchandDUneAutreSociete('P');

        $this->artisan('beninlink:retours-annules --societe=' . $autre->company_id)
            ->expectsOutputToContain('Aucun retour annulé sans réversion')
            ->assertExitCode(0);

        $this->assertSame(-590.0, $this->soldeMarchand(), 'le colis de la société voisine reste intact');

        $this->artisan('beninlink:retours-annules --societe=' . $this->marchand->company_id . ' --corriger')
            ->assertExitCode(0);

        $this->assertSame(0.0, $this->soldeMarchand());
    }

    // ---- ce qu'elle refuse de toucher -------------------------------------

    /**
     * Un relevé déjà émis a facturé le retour. Lui rendre l'argent au solde
     * sans rien dire du document mettrait les deux en désaccord — l'invariant
     * même de D9. La commande montre, et s'arrête là.
     */
    public function test_un_colis_deja_facture_est_montre_mais_jamais_touche(): void
    {
        $this->recevoirLeRetour();
        $this->annulerALAncienne();

        $releve = new Invoice();
        $releve->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'invoice_id' => 'CO-2026-000001',
            'total_charge' => 500,
            'current_payable' => -500,
        ])->save();

        $colis = Parcel::find($this->colis->id);
        $colis->invoice_id = $releve->id;
        $colis->save();

        $this->artisan('beninlink:retours-annules --corriger')
            ->expectsOutputToContain('figés')
            ->assertExitCode(1);

        $this->assertSame(-590.0, $this->soldeMarchand());
        $this->assertSame(500.0, (float) Parcel::find($this->colis->id)->return_charges);
    }
}
