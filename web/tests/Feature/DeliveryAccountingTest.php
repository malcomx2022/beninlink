<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\VatStatement;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * La livraison — l'étape la plus lourde du socle sur le plan comptable.
 *
 * Un colis livré déplace **quatre** jeux de comptes d'un coup, et le socle les
 * écrivait sans aucun test :
 *
 * | Compte | Mouvement |
 * |---|---|
 * | Marchand | + encaissement, − frais, − TVA → net à reverser |
 * | Livreur | + sa course, − l'encaissement qu'il détient |
 * | Transporteur | − la course du livreur, + les frais de livraison |
 * | TVA | + la TVA collectée |
 *
 * Ce fichier fixe ces mouvements. Les montants du décor sont choisis pour être
 * vérifiables de tête (voir `BuildsAccountingFixtures`) : c'est le mouvement
 * qu'on teste, pas la tarification.
 */
class DeliveryAccountingTest extends TestCase
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
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());

        $this->livreur = $this->livreur();
        $this->colis = $this->colisConfie($this->marchand, $this->livreur, 'BL-LIVRE');
    }

    private function livrer(?int $id = null): bool
    {
        return app(ParcelInterface::class)->parcelDelivered($id ?? $this->colis->id, new Request());
    }

    private function soldeMarchand(): float
    {
        return (float) Merchant::find($this->marchand->id)->current_balance;
    }

    private function soldeLivreur(): float
    {
        return (float) DeliveryMan::find($this->livreur->id)->current_balance;
    }

    // ---- les quatre comptes ----------------------------------------------

    /**
     * Le marchand encaisse ce que le client a payé, moins les frais et la TVA.
     * Le résultat doit être exactement le `current_payable` porté par le colis :
     * c'est le montant que le relevé de règlement reprendra.
     */
    public function test_the_merchant_is_credited_with_the_net_payable(): void
    {
        $this->assertTrue($this->livrer());

        $this->assertSame(self::PAYABLE, $this->soldeMarchand());
        $this->assertSame(self::CASH - self::CHARGES - self::VAT, $this->soldeMarchand());
    }

    /**
     * Chaque ligne nomme son marchand — sans quoi elle n'apparaît nulle part.
     *
     * L'écran « Mes relevés », côté panneau comme côté API, lit
     * `MerchantStatement::where('merchant_id', …)`. Les **treize** écritures du
     * cycle de vie d'un colis ne renseignaient pas cette colonne : le relevé du
     * marchand ne montrait donc aucune ligne de livraison — ni l'encaissement,
     * ni les frais, ni la TVA. Son solde bougeait sans rien pour l'expliquer,
     * ce qui est exactement le contraire de ce qu'un relevé doit faire.
     */
    public function test_every_statement_line_names_its_merchant(): void
    {
        $this->livrer();

        $this->assertSame(
            0,
            MerchantStatement::whereNull('merchant_id')->count(),
            'Une ligne de releve sans marchand n\'apparait sur le releve de personne.',
        );
        $this->assertSame(3, MerchantStatement::where('merchant_id', $this->marchand->id)->count());
    }

    /** Trois lignes au relevé du marchand : l'encaissement, les frais, la TVA. */
    public function test_the_merchant_statement_details_the_three_movements(): void
    {
        $this->livrer();

        $lignes = MerchantStatement::where('parcel_id', $this->colis->id)->get();

        $this->assertSame(self::CASH, (float) $lignes->firstWhere('type', StatementType::INCOME)->amount);
        $this->assertEqualsCanonicalizing(
            [self::CHARGES, self::VAT],
            $lignes->where('type', StatementType::EXPENSE)->pluck('amount')->map(fn ($m) => (float) $m)->all(),
        );
    }

    /**
     * Le livreur gagne sa course et **doit** l'argent qu'il a encaissé : son
     * solde descend de la différence. C'est cette dette que la remise
     * d'espèces au hub viendra solder.
     */
    public function test_the_deliveryman_owes_the_cash_he_collected_less_his_fee(): void
    {
        $this->livrer();

        $this->assertSame(self::COURSE - self::CASH, $this->soldeLivreur());

        $lignes = DeliverymanStatement::where('parcel_id', $this->colis->id)->get();
        $this->assertSame(self::COURSE, (float) $lignes->firstWhere('type', StatementType::INCOME)->amount);
        $this->assertSame(self::CASH, (float) $lignes->firstWhere('type', StatementType::EXPENSE)->amount);
    }

    /** Le transporteur paie la course et encaisse les frais de livraison. */
    public function test_the_courier_pays_the_round_and_earns_the_delivery_charge(): void
    {
        $this->livrer();

        $lignes = CourierStatement::where('parcel_id', $this->colis->id)->get();

        $this->assertSame(self::COURSE, (float) $lignes->firstWhere('type', StatementType::EXPENSE)->amount);
        $this->assertSame(self::CHARGES, (float) $lignes->firstWhere('type', StatementType::INCOME)->amount);
    }

    /** La TVA collectée est isolée sur son propre relevé (chantier 4). */
    public function test_the_vat_collected_is_booked_on_its_own_statement(): void
    {
        $this->livrer();

        $tva = VatStatement::where('parcel_id', $this->colis->id)->firstOrFail();
        $this->assertSame(self::VAT, (float) $tva->amount);
        $this->assertSame(StatementType::INCOME, (int) $tva->type);
    }

    /** Et le colis change d'état. */
    public function test_the_parcel_becomes_delivered(): void
    {
        $this->livrer();

        $this->assertSame(ParcelStatus::DELIVERED, (int) Parcel::find($this->colis->id)->status);
    }

    /**
     * Quand la livraison a été reprogrammée, c'est le livreur de la **dernière**
     * reprogrammation qui est payé, pas celui de l'affectation d'origine.
     */
    public function test_a_rescheduled_round_pays_the_last_deliveryman(): void
    {
        $second = $this->livreur('2');
        $this->evenement($this->colis, $second, ParcelStatus::DELIVERY_RE_SCHEDULE);

        $this->livrer();

        $this->assertSame(0.0, (float) DeliveryMan::find($this->livreur->id)->current_balance);
        $this->assertSame(self::COURSE - self::CASH, (float) DeliveryMan::find($second->id)->current_balance);
    }

    // ---- ce qui ne doit pas arriver --------------------------------------

    /**
     * Livrer deux fois le même colis doublait **tous** les comptes : le marchand
     * crédité deux fois du même encaissement, le livreur endetté deux fois, la
     * TVA déclarée en double.
     *
     * Ce n'est pas théorique : l'app livreur appelle cette étape
     * (`POST deliveryman/parcel/delivered/{id}`) et un renvoi sur une connexion
     * instable suffit. C'est le même défaut que F1 sur les recharges, à l'étape
     * la plus exposée du socle.
     */
    public function test_delivering_the_same_parcel_twice_changes_nothing(): void
    {
        $this->assertTrue($this->livrer());

        $this->assertFalse($this->livrer(), 'Un colis deja livre ne se relivre pas.');

        $this->assertSame(self::PAYABLE, $this->soldeMarchand());
        $this->assertSame(self::COURSE - self::CASH, $this->soldeLivreur());
        $this->assertSame(3, MerchantStatement::count());
        $this->assertSame(1, VatStatement::count());
    }

    /**
     * Le colis d'un autre transporteur n'est pas livrable depuis ici. Le socle
     * lisait `Parcel::find($id)` nu : un administrateur créditait le marchand
     * d'une autre société en changeant l'identifiant posté.
     */
    public function test_a_parcel_of_another_company_cannot_be_delivered(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $livreurAilleurs = $this->livreur('9', $ailleurs->company_id);
        $colisAilleurs = $this->colisConfie($ailleurs, $livreurAilleurs, 'BL-AILLEURS', [
            'company_id' => $ailleurs->company_id,
        ]);

        $this->assertFalse($this->livrer($colisAilleurs->id));

        $this->assertSame(0.0, (float) Merchant::find($ailleurs->id)->current_balance);
        $this->assertSame(0, MerchantStatement::count());
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, (int) Parcel::find($colisAilleurs->id)->status);
    }

    /**
     * Les écritures sont atomiques. Un colis jamais confié à un livreur fait
     * échouer le calcul de la course au milieu de l'étape : le socle écrivait
     * les quatre jeux de comptes hors transaction, si bien qu'un incident
     * laissait des livres à moitié faits — et impossibles à rattraper, puisque
     * relancer l'étape doublait l'autre moitié.
     *
     * Ici l'échec survient après l'écriture de l'événement « Livré » : c'est
     * lui qui doit disparaître avec le reste.
     */
    public function test_a_failure_mid_way_leaves_no_half_written_books(): void
    {
        $orphelin = new Parcel();
        $orphelin->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'customer_name' => 'Client',
            'customer_address' => 'Cotonou',
            'cash_collection' => self::CASH,
            'total_delivery_amount' => self::CHARGES,
            'vat_amount' => self::VAT,
            'current_payable' => self::PAYABLE,
            'tracking_id' => 'BL-SANS-LIVREUR',
            'status' => ParcelStatus::RECEIVED_WAREHOUSE,
        ])->save();

        $this->assertFalse($this->livrer($orphelin->id));

        $this->assertSame(0.0, $this->soldeMarchand());
        $this->assertSame(0, MerchantStatement::count());
        $this->assertSame(0, VatStatement::count());
        // L'événement « Livré » écrit en tête d'étape ne survit pas non plus.
        $this->assertSame(0, \App\Models\Backend\ParcelEvent::where('parcel_id', $orphelin->id)->count());
        $this->assertSame(ParcelStatus::RECEIVED_WAREHOUSE, (int) Parcel::find($orphelin->id)->status);
    }

    /**
     * Le refus ne bloque pas le rattrapage légitime : une livraison annulée
     * puis refaite passe.
     *
     * C'est la seule manière dont la garde d'unicité pourrait gêner un usage
     * réel — l'annulation remet le colis au statut « livreur assigné », donc
     * l'étape redevient franchissable, et les comptes repartent du bon état.
     */
    public function test_a_cancelled_delivery_can_be_redone(): void
    {
        $this->livrer();
        $this->assertTrue(app(ParcelInterface::class)->parcelDeliveredCancel($this->colis->id, new Request()));

        $this->assertTrue($this->livrer(), 'Une livraison annulee doit pouvoir etre refaite.');
        $this->assertSame(self::PAYABLE, $this->soldeMarchand());
    }

    /**
     * Le contrat vu de l'app livreur : un second envoi rend **422**, pas un
     * « livré » de complaisance.
     *
     * Le contrôleur ignorait le retour du repository et répondait toujours 200.
     * Tant que l'étape se rejouait, cela « marchait » ; maintenant qu'elle
     * refuse, un 200 ferait croire au livreur qu'un renvoi a enregistré quelque
     * chose. C'est justement dans ce cas — réseau instable, renvoi — que le
     * doublon se produisait.
     */
    public function test_the_deliveryman_api_reports_a_refused_second_delivery(): void
    {
        config(['rxcourier.api_key' => 'cle-de-test']);
        \Laravel\Sanctum\Sanctum::actingAs($this->livreur->user->fresh(), ['deliveryman']);

        $entetes = ['apiKey' => 'cle-de-test'];
        $url = '/api/v10/deliveryman/parcel/delivered/' . $this->colis->id;

        $this->postJson($url, [], $entetes)->assertOk();
        $this->postJson($url, [], $entetes)->assertStatus(422);

        $this->assertSame(self::PAYABLE, $this->soldeMarchand());
        $this->assertSame(3, MerchantStatement::count());
    }

    /**
     * L'inverse : une passerelle SMS en panne ne défait pas une livraison qui a
     * eu lieu. Le socle envoyait les SMS **dans le même `try`** que les
     * écritures — un opérateur injoignable rendait donc « une erreur est
     * survenue » à l'écran, alors que les livres étaient bel et bien écrits.
     */
    public function test_a_failing_sms_does_not_undo_a_delivery(): void
    {
        $this->app->bind(\App\Http\Services\SmsService::class, fn () => new class extends \App\Http\Services\SmsService {
            public function sendSms($to = null, $message = null, $parcel = null)
            {
                throw new \RuntimeException('passerelle SMS injoignable');
            }
        });

        $ok = app(ParcelInterface::class)->parcelDelivered(
            $this->colis->id,
            new Request(['send_sms_customer' => 'on']),
        );

        $this->assertTrue($ok);
        $this->assertSame(self::PAYABLE, $this->soldeMarchand());
        $this->assertSame(ParcelStatus::DELIVERED, (int) Parcel::find($this->colis->id)->status);
    }
}
