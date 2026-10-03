<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Wallet;
use App\Models\CashReceivedFromDeliveryman;
use App\Models\Config;
use App\Repositories\Invoice\InvoiceInterface;
use App\Services\Invoicing\SyscohadaJournal;
use App\Services\Parcel\ChargeCalculator;
use App\Services\Parcel\ReturnVat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D2 — le plan de comptes signé** (S73, 2026-10-03).
 *
 * Les huit questions de `docs/guides/comptabilite/plan-de-comptes.md` sont
 * tranchées par le porteur. Ce fichier **fige** ce qui en découle — comptes,
 * journaux, auxiliaire, taux, règle d'arrondi, date de l'écriture de banque,
 * flux journalisés — pour qu'une modification ultérieure soit **visible et
 * délibérée** : on change le test en même temps que la décision, jamais l'un
 * sans l'autre.
 *
 * Trois numéros restent des placeholders « à valider » par l'expert-comptable
 * (COD dédié, avances reçues, transit livreurs) : ils sont figés *aussi*, à
 * leur valeur d'attente, pour que leur remplacement soit un acte.
 */
class PlanDeComptesSigneTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    /** Le plan signé — codes et libellés, tels que `config/syscohada.php` doit les porter. */
    private const PLAN = [
        'customers' => ['code' => '4111', 'label' => 'Clients'],
        'delivery_services' => ['code' => '7061', 'label' => 'Prestations de services de livraison'],
        'vat_collected' => ['code' => '4431', 'label' => 'État, TVA facturée sur ventes'],
        'cod_liability' => ['code' => '4712', 'label' => 'COD encaissé pour compte de marchands (compte dédié — numéro à valider)'],
        'bank' => ['code' => '521', 'label' => 'Banques locales'],
        'wallet_advances' => ['code' => '4191', 'label' => 'Clients, avances reçues — portefeuille marchand (à valider)'],
        'courier_transit' => ['code' => '4713', 'label' => 'Livreurs, fonds en transit (à valider)'],
        'cash' => ['code' => '571', 'label' => 'Caisse'],
    ];

    private const JOURNAUX = ['sales' => 'VE', 'settlement' => 'OD', 'bank' => 'BQ', 'cash' => 'CA'];

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->payment_period = 0;
        $this->marchand->save();
        $this->actingAs($this->marchand->user->fresh());
    }

    // ---- ce qui est figé -------------------------------------------------------

    public function test_les_comptes_et_les_journaux_sont_ceux_du_plan_signe(): void
    {
        $this->assertSame(self::PLAN, config('syscohada.accounts'), 'un compte a changé : changer la décision avec lui (D2)');
        $this->assertSame(self::JOURNAUX, config('syscohada.journals'), 'un code de journal a changé (D2 q.4)');
    }

    /** Q1 : auxiliaire par marchand par défaut, sur les seuls comptes de tiers. */
    public function test_l_auxiliaire_par_marchand_est_le_defaut_sur_les_comptes_de_tiers(): void
    {
        $this->assertSame('merchant_code', config('syscohada.auxiliary'));
        $this->assertSame(13, config('syscohada.auxiliary_length'));
        $this->assertSame(['customers', 'cod_liability', 'wallet_advances'], config('syscohada.auxiliarised'));
    }

    /** Q3 : 4431 et le taux société de 18 % (D1). */
    public function test_la_tva_est_au_4431_au_taux_societe_de_18_pour_cent(): void
    {
        $this->assertSame('4431', config('syscohada.accounts.vat_collected.code'));
        $this->assertSame(
            '18',
            (string) Config::where('company_id', $this->marchand->company_id)->where('key', 'vat_rate')->value('value'),
        );
    }

    /** Q7 : au franc le plus proche, au seul endroit où un taux devient des francs. */
    public function test_l_arrondi_est_au_franc_le_plus_proche(): void
    {
        $percentage = new \ReflectionMethod(ChargeCalculator::class, 'percentage');
        $percentage->setAccessible(true);

        // 18 % de 1 680 F font 302,40 F : 302, pas 302,4 ni 303.
        $this->assertSame(302.0, $percentage->invoke(new ChargeCalculator(), 1680, 18));
        $this->assertSame(302, ReturnVat::arrondi(1680, 18));
        // 2,5 % de 17 350 F font 433,75 F : 434.
        $this->assertSame(434.0, $percentage->invoke(new ChargeCalculator(), 17350, 2.5));
    }

    /** Q8 : les abonnements SaaS n'ont AUCUNE écriture côté transporteur. */
    public function test_les_abonnements_saas_ne_donnent_aucune_ecriture(): void
    {
        $source = file_get_contents(app_path('Services/Invoicing/SyscohadaJournal.php'));
        $this->assertStringNotContainsStringIgnoringCase('subscription', $source);
        $this->assertStringNotContainsStringIgnoringCase('abonnement', preg_replace('#/\*.*?\*/|//.*#s', '', $source));
    }

    // ---- Q5 : la date de l'écriture de banque ------------------------------------

    private function releveEmisLe(string $jour): Invoice
    {
        $this->travelTo(Carbon::parse($jour));
        $this->colisConfie($this->marchand, $this->livreur(), 'BL-' . $jour, [
            'status' => ParcelStatus::DELIVERED,
            'delivery_date' => $jour,
        ]);
        $releve = app(InvoiceInterface::class)->store($this->marchand->id);
        $this->assertInstanceOf(Invoice::class, $releve);

        return $releve->fresh();
    }

    private function marquer(Invoice $releve, int $statut): void
    {
        $request = new Request(['id' => $releve->id, 'invoice_id' => $releve->invoice_id, 'status' => $statut]);
        $this->assertTrue(app(InvoiceInterface::class)->statusUpdate($request, $releve->merchant_id));
    }

    public function test_l_ecriture_de_banque_prend_la_date_de_l_ordre_de_virement(): void
    {
        $releve = $this->releveEmisLe('2026-08-28');
        $this->assertNull($releve->paid_on);
        $this->assertSame([], SyscohadaJournal::reversement($releve), 'pas de banque tant que non payé');

        // Le virement part le 3 septembre : c'est ce jour-là qu'on marque payé.
        $this->travelTo(Carbon::parse('2026-09-03'));
        $this->marquer($releve, InvoiceStatus::PAID);

        $releve = $releve->fresh();
        $this->assertSame('2026-09-03', (string) $releve->paid_on);
        $banque = SyscohadaJournal::reversement($releve);
        $this->assertCount(2, $banque);
        $this->assertSame('2026-09-03', $banque[0]['date'], 'la banque est datée du virement, pas de l’émission');
        $this->assertSame('BQ', $banque[0]['journal']);

        // L'extrait d'août porte les ventes, pas la banque ; celui de septembre, l'inverse.
        $aout = SyscohadaJournal::periode($this->marchand->company_id, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
        $septembre = SyscohadaJournal::periode($this->marchand->company_id, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $journauxAout = array_unique(array_column($aout, 'journal'));
        sort($journauxAout);
        $this->assertSame(['OD', 'VE'], $journauxAout, 'août : ventes et compensation, pas de banque');
        $this->assertSame(['BQ'], array_values(array_unique(array_column($septembre, 'journal'))));

        // Reculer le statut efface la date : une nouvelle mise en paiement en posera une autre.
        $this->marquer($releve, InvoiceStatus::UNPAID);
        $this->assertNull($releve->fresh()->paid_on);
    }

    /** Un relevé payé avant que `paid_on` existe garde l'émission pour date — comme avant. */
    public function test_un_releve_paye_sans_date_de_virement_retombe_sur_l_emission(): void
    {
        $releve = $this->releveEmisLe('2026-08-28');
        $releve->status = InvoiceStatus::PAID;
        $releve->paid_on = null;
        $releve->save();

        $banque = SyscohadaJournal::reversement($releve->fresh());
        $this->assertSame('2026-08-28', $banque[0]['date']);
        $this->assertCount(2, array_filter(
            SyscohadaJournal::periode($this->marchand->company_id, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')),
            fn (array $l) => $l['journal'] === 'BQ',
        ));
    }

    // ---- Q8 : les deux flux journalisés -----------------------------------------

    public function test_une_recharge_approuvee_est_une_avance_recue_du_marchand(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 10:00:00'));
        $recharge = new Wallet();
        $recharge->forceFill([
            'company_id' => $this->marchand->company_id,
            'user_id' => $this->marchand->user_id,
            'merchant_id' => $this->marchand->id,
            'transaction_id' => 'FP-TEST-1',
            'amount' => 20000,
            'type' => WalletType::INCOME,
            'status' => WalletStatus::APPROVED,
        ])->save();

        // Une recharge encore en attente n'a rien crédité : pas d'écriture.
        $attente = new Wallet();
        $attente->forceFill([
            'company_id' => $this->marchand->company_id,
            'user_id' => $this->marchand->user_id,
            'merchant_id' => $this->marchand->id,
            'amount' => 5000,
            'type' => WalletType::INCOME,
            'status' => WalletStatus::PENDING,
        ])->save();

        $lignes = SyscohadaJournal::recharges($this->marchand->company_id, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->marchand->merchant_unique_id));

        $this->assertCount(2, $lignes, 'une seule recharge approuvée, deux lignes');
        $this->assertSame(['BQ', 'BQ'], array_column($lignes, 'journal'));
        $this->assertSame(['FP-TEST-1', 'FP-TEST-1'], array_column($lignes, 'piece'));
        $this->assertSame('521', $lignes[0]['compte']);
        $this->assertSame(20000, $lignes[0]['debit']);
        $this->assertSame('4191' . $code, $lignes[1]['compte'], 'les avances reçues sont un compte de tiers : auxiliarisé');
        $this->assertSame(20000, $lignes[1]['credit']);
        $this->assertSame('2026-09-10', $lignes[0]['date']);
        $this->assertSame([], SyscohadaJournal::balance($lignes)['desequilibrees']);
    }

    public function test_une_remise_d_especes_du_livreur_transite_avant_la_caisse(): void
    {
        $livreur = $this->livreur('R');
        $remise = new CashReceivedFromDeliveryman();
        $remise->forceFill([
            'company_id' => $this->marchand->company_id,
            'user_id' => $this->marchand->user_id,
            'delivery_man_id' => $livreur->id,
            'amount' => 15000,
            'date' => '2026-09-12 17:30:00',
            'note' => 'remise du soir',
        ])->save();

        $lignes = SyscohadaJournal::remises($this->marchand->company_id, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertCount(2, $lignes);
        $this->assertSame(['CA', 'CA'], array_column($lignes, 'journal'));
        $this->assertSame('571', $lignes[0]['compte']);
        $this->assertSame(15000, $lignes[0]['debit']);
        $this->assertSame('4713', $lignes[1]['compte'], 'le transit livreurs reste collectif : pas d’auxiliaire marchand');
        $this->assertSame(15000, $lignes[1]['credit']);
        $this->assertSame('REM-' . $remise->id, $lignes[0]['piece']);
        $this->assertSame('2026-09-12', $lignes[0]['date']);
    }

    /** L'extrait de période assemble les quatre flux, société par société, sans `settings()`. */
    public function test_l_extrait_de_periode_assemble_les_flux_et_reste_equilibre(): void
    {
        $releve = $this->releveEmisLe('2026-09-05');
        $this->travelTo(Carbon::parse('2026-09-20'));
        $this->marquer($releve, InvoiceStatus::PAID);

        $recharge = new Wallet();
        $recharge->forceFill([
            'company_id' => $this->marchand->company_id, 'user_id' => $this->marchand->user_id,
            'merchant_id' => $this->marchand->id, 'amount' => 20000,
            'type' => WalletType::INCOME, 'status' => WalletStatus::APPROVED,
        ])->save();
        $remise = new CashReceivedFromDeliveryman();
        $remise->forceFill([
            'company_id' => $this->marchand->company_id, 'user_id' => $this->marchand->user_id,
            'delivery_man_id' => $this->livreur('S')->id, 'amount' => 15000, 'date' => '2026-09-21 09:00:00',
        ])->save();

        $lignes = SyscohadaJournal::periode($this->marchand->company_id, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $journaux = array_count_values(array_column($lignes, 'journal'));

        $this->assertSame(3, $journaux['VE']);
        $this->assertSame(2, $journaux['OD']);
        $this->assertSame(4, $journaux['BQ'], 'le reversement du relevé et la recharge');
        $this->assertSame(2, $journaux['CA']);
        $this->assertSame([], SyscohadaJournal::balance($lignes)['desequilibrees']);

        // `--payes` : les relevés payés seuls, sans recharges ni remises.
        $payes = SyscohadaJournal::periode($this->marchand->company_id, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'), true);
        $this->assertSame(['VE', 'OD', 'BQ'], array_values(array_unique(array_column($payes, 'journal'))));

        // Et la commande, sur toutes les sociétés, sans option : équilibrée.
        $this->artisan('beninlink:journal-syscohada', ['--du' => '2026-09-01', '--au' => '2026-09-30'])
            ->expectsOutputToContain('Équilibré')
            ->assertSuccessful();

        // Une autre société ne voit rien de tout cela.
        $autre = $this->marchandDUneAutreSociete('Z');
        $this->assertSame([], SyscohadaJournal::periode($autre->company_id, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30')));
    }
}
