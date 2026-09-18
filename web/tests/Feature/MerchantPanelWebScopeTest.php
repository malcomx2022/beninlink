<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\MerchantPanel\FraudController;
use App\Http\Controllers\Backend\MerchantPanel\InvoiceController;
use App\Http\Controllers\Backend\MerchantPanel\MerchantParcelController;
use App\Http\Controllers\Backend\MerchantPanel\PaymentAccountController;
use App\Http\Controllers\Backend\MerchantPanel\PaymentRequestController;
use App\Http\Controllers\Backend\MerchantPanel\ShopsController;
use App\Http\Controllers\Backend\MerchantPanel\SupportController;
use App\Http\Controllers\Backend\MerchantPanel\WalletController;
use App\Models\Backend\Fraud;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\Support;
use App\Models\Backend\Wallet;
use App\Models\MerchantPayment;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S29, seconde passe — le panneau marchand **web**.
 *
 * Ces dépôts sont scopés depuis S7, S17 et S18 : tout passe par un `owned…()`
 * sur le marchand ou l'utilisateur connecté. Mais ce qui le prouvait,
 * `TenantIsolationTest`, appelle les routes de l'**API**. Les écrans web sont
 * d'autres contrôleurs, et le filet exige la preuve au point d'entrée qu'il
 * inscrit — c'est tout l'objet de la leçon **F6** : une déclaration qui pointe un
 * test incapable de toucher la route ne prouve rien.
 *
 * Deux marchands de la **MÊME société**. Ce n'est pas un détail : c'est le cas
 * que la décision S7 nomme comme le plus fréquent, et celui qu'un *global scope*
 * Eloquent sur `company_id` n'aurait jamais attrapé. La frontière ici n'est pas
 * la société, c'est le marchand.
 *
 * Le portefeuille est l'exception : ses trois routes sont des écrans
 * d'ADMINISTRATION (`admin/wallet-request/…`), donc scopés par société, via le
 * garde `proprieteVerifiee()` posé dans le contrôleur.
 */
class MerchantPanelWebScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private User $moi;
    private Merchant $monMarchand;
    private User $lui;
    private Merchant $sonMarchand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        [$this->moi, $this->monMarchand] = $this->marchandDe('a');
        [$this->lui, $this->sonMarchand] = $this->marchandDe('b');

        $this->actingAs($this->moi);
    }

    /**
     * Chaque écran du panneau, avec l'identifiant d'une ressource du VOISIN.
     * Aucun ne doit répondre : tous doivent s'arrêter en 404.
     */
    public function test_no_merchant_panel_screen_serves_a_neighbours_resource(): void
    {
        $ouverts = [];

        foreach ($this->ecransDuVoisin() as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans du panneau marchand qui servent la ressource d'un AUTRE "
            . "marchand de la même société :\n - " . implode("\n - ", $ouverts));
    }

    /** Et rien n'a été écrit ni supprimé au passage. */
    public function test_nothing_of_the_neighbour_was_written_or_destroyed(): void
    {
        foreach ($this->ecransDuVoisin() as $appel) {
            try {
                $appel();
            } catch (\Throwable $e) {
                // le refus est l'objet du test précédent
            }
        }

        $this->assertDatabaseHas('supports', ['user_id' => $this->lui->id]);
        $this->assertDatabaseHas('frauds', ['created_by' => $this->lui->id]);
        $this->assertDatabaseHas('merchant_payments', ['merchant_id' => $this->sonMarchand->id]);
        $this->assertDatabaseHas('payments', ['merchant_id' => $this->sonMarchand->id]);
        $this->assertDatabaseHas('merchant_shops', ['merchant_id' => $this->sonMarchand->id]);
        $this->assertDatabaseHas('parcels', ['merchant_id' => $this->sonMarchand->id]);
    }

    /**
     * Le contrôle négatif du filet : les mêmes dépôts trouvent bien MES
     * ressources. Sans lui, un `owned…()` qui ne renverrait jamais rien ferait
     * passer le test ci-dessus sans rien prouver.
     */
    public function test_my_own_resources_are_still_reachable(): void
    {
        $this->assertNotNull(app(\App\Repositories\MerchantPanel\Support\SupportInterface::class)->get($this->ticketDe($this->moi)->id));
        $this->assertNotNull(app(\App\Repositories\MerchantPanel\Fraud\FraudInterface::class)->get($this->fraudeDe($this->moi)->id));
        $this->assertNotNull(app(\App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountInterface::class)->edit($this->compteDe($this->monMarchand)->id));
        $this->assertNotNull(app(\App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface::class)->get($this->demandeDe($this->monMarchand)->id));
        $this->assertNotNull(app(\App\Repositories\MerchantPanel\Shops\ShopsInterface::class)->get($this->boutiqueDe($this->monMarchand)->id));
        $this->assertNotNull(app(\App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface::class)->get($this->colisDe($this->monMarchand)->id));
    }

    /**
     * Le portefeuille : trois écrans d'administration qui DÉPLACENT DE L'ARGENT.
     * La frontière est la société, et le garde vit dans le contrôleur.
     */
    public function test_the_wallet_requests_of_another_company_cannot_be_settled(): void
    {
        $voisine = $this->rechargeDe(Merchant::where('company_id', 2)->firstOrFail());
        $controleur = app(WalletController::class);

        foreach (['approve', 'reject', 'delete'] as $methode) {
            try {
                $controleur->{$methode}($voisine->id);
                $this->fail("WalletController::{$methode}() a agi sur la recharge d'une autre société");
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }

        // `forceCreate` ne pose pas `status` : la valeur vient du defaut SQL, donc
        // l'instance en memoire l'a encore a null. On lit la ligne ecrite.
        $this->assertDatabaseHas('wallets', ['id' => $voisine->id, 'status' => $voisine->fresh()->status]);
    }


    /**
     * Les ÉCRITURES du panneau, par leur dépôt — c'est là que vit la frontière,
     * et c'est la moitié que S23 et S26 avaient oubliée ailleurs. Ici elles
     * étaient déjà scopées ; on l'inscrit pour que les routes le soient aussi.
     */
    public function test_no_write_of_the_panel_touches_a_neighbours_resource(): void
    {
        $ticket = $this->ticketDe($this->lui);
        $fraude = $this->fraudeDe($this->lui);
        $compte = $this->compteDe($this->sonMarchand);
        $demande = $this->demandeDe($this->sonMarchand);
        $boutique = $this->boutiqueDe($this->sonMarchand);
        $colis = $this->colisDe($this->sonMarchand);

        $ecritures = [
            'support/delete' => fn () => app(\App\Repositories\MerchantPanel\Support\SupportInterface::class)->delete($ticket->id),
            'fraud/delete' => fn () => app(\App\Repositories\MerchantPanel\Fraud\FraudInterface::class)->delete($fraude->id),
            'payment-account/delete' => fn () => app(\App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountInterface::class)->delete($compte->id),
            'payment-request/delete' => fn () => app(\App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface::class)->delete($demande->id),
            'shops/delete' => fn () => app(\App\Repositories\MerchantPanel\Shops\ShopsInterface::class)->delete($boutique->id),
            'parcel/delete' => fn () => app(\App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface::class)->delete($colis->id, $this->monMarchand->id),
        ];

        $effectuees = [];
        foreach ($ecritures as $nom => $appel) {
            if ($appel()) {
                $effectuees[] = $nom;
            }
        }

        $this->assertSame([], $effectuees, "Écritures abouties sur la ressource d'un autre marchand :\n - "
            . implode("\n - ", $effectuees));

        $this->assertNotNull(Support::find($ticket->id));
        $this->assertNotNull(Fraud::find($fraude->id));
        $this->assertNotNull(MerchantPayment::find($compte->id));
        $this->assertNotNull(Payment::find($demande->id));
        $this->assertNotNull(MerchantShops::find($boutique->id));
        $this->assertNotNull(Parcel::find($colis->id));
    }

    /** Et les trois écritures qui prennent une requête, une par une. */
    public function test_no_requested_write_of_the_panel_touches_a_neighbour(): void
    {
        $ticket = $this->ticketDe($this->lui);
        $boutique = $this->boutiqueDe($this->sonMarchand);
        $colis = $this->colisDe($this->sonMarchand);

        $sujet = $ticket->subject;
        $nom = $boutique->name;
        $client = $colis->customer_name;

        $requete = new \Illuminate\Http\Request([
            'department_id' => $ticket->department_id, 'service' => 'Détournement', 'priority' => 'high',
            'subject' => 'Réécrit', 'description' => 'Réécrit', 'date' => now()->toDateString(),
            'name' => 'Détournée', 'contact_no' => '0022997000999', 'address' => 'Ailleurs', 'status' => 1,
        ]);

        $this->assertFalse((bool) app(\App\Repositories\MerchantPanel\Support\SupportInterface::class)->update($ticket->id, $requete));
        $this->assertFalse((bool) app(\App\Repositories\MerchantPanel\Shops\ShopsInterface::class)->update($boutique->id, $requete));
        $this->assertFalse((bool) app(\App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface::class)->update($colis->id, $requete, $this->monMarchand->id));
        $this->assertFalse((bool) app(\App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface::class)->statusUpdate($colis->id, 5));

        $this->assertSame($sujet, $ticket->fresh()->subject);
        $this->assertSame($nom, $boutique->fresh()->name);
        $this->assertSame($client, $colis->fresh()->customer_name);
    }

    /* ─────────────────────── les écrans, un par un ──────────────────────── */

    private function ecransDuVoisin(): array
    {
        $ticket = $this->ticketDe($this->lui);
        $fraude = $this->fraudeDe($this->lui);
        $compte = $this->compteDe($this->sonMarchand);
        $demande = $this->demandeDe($this->sonMarchand);
        $boutique = $this->boutiqueDe($this->sonMarchand);
        $colis = $this->colisDe($this->sonMarchand);

        $support = app(SupportController::class);
        $fraud = app(FraudController::class);
        $paymentAccount = app(PaymentAccountController::class);
        $paymentRequest = app(PaymentRequestController::class);
        $shops = app(ShopsController::class);
        $parcel = app(MerchantParcelController::class);
        $invoice = app(InvoiceController::class);

        return [
            'support/edit' => fn () => $support->edit($ticket->id),
            'support/view' => fn () => $support->view($ticket->id),
            'fraud/edit' => fn () => $fraud->edit($fraude->id),
            'payment-account/edit' => fn () => $paymentAccount->edit($compte->id),
            'payment-request/edit' => fn () => $paymentRequest->edit($demande->id),
            'shops/edit' => fn () => $shops->edit($boutique->id),
            'parcel/details' => fn () => $parcel->details($colis->id),
            'parcel/edit' => fn () => $parcel->edit($colis->id),
            'parcel/logs' => fn () => $parcel->logs($colis->id),
            'parcel/clone' => fn () => $parcel->duplicate($colis->id),
            'parcel/destroy' => fn () => $parcel->destroy($colis->id),
            'invoice/details' => fn () => $invoice->InvoiceDetails('FACT-DU-VOISIN'),
        ];
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    /** @return array{0: User, 1: Merchant} */
    private function marchandDe(string $suffixe): array
    {
        $utilisateur = new User();
        $utilisateur->company_id = settings()->id;
        $utilisateur->name = 'Marchand ' . $suffixe;
        $utilisateur->email = 'marchand.' . $suffixe . '@example.test';
        $utilisateur->mobile = '002299700' . ord($suffixe);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        $marchand = Merchant::forceCreate([
            'company_id' => settings()->id,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME ' . $suffixe,
            'current_balance' => 0,
        ]);

        return [$utilisateur->fresh(), $marchand];
    }

    private function ticketDe(User $auteur): Support
    {
        return Support::forceCreate([
            'user_id' => $auteur->id,
            'department_id' => \App\Models\Backend\Department::first()?->id,
            'service' => 'Livraison',
            'priority' => 'high',
            'subject' => 'Ticket de ' . $auteur->name,
            'description' => 'Contenu confidentiel',
            'date' => now()->toDateString(),
        ]);
    }

    private function fraudeDe(User $auteur): Fraud
    {
        return Fraud::forceCreate([
            'company_id' => $auteur->company_id,
            'created_by' => $auteur->id,
            'phone' => '0022997654321',
            'name' => 'Client signale par ' . $auteur->name,
            'details' => 'Colis refuse trois fois',
        ]);
    }

    private function compteDe(Merchant $marchand): MerchantPayment
    {
        return MerchantPayment::forceCreate([
            'merchant_id' => $marchand->id,
            'payment_method' => 'mobile',
            'holder_name' => 'Titulaire ' . $marchand->business_name,
            'mobile_company' => 'MTN MoMo',
            'mobile_no' => '0022996000000',
        ]);
    }

    private function demandeDe(Merchant $marchand): Payment
    {
        return Payment::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'amount' => 50000,
        ]);
    }

    private function boutiqueDe(Merchant $marchand): MerchantShops
    {
        return MerchantShops::forceCreate([
            'merchant_id' => $marchand->id,
            'name' => 'Boutique de ' . $marchand->business_name,
            'contact_no' => '0022997001111',
            'address' => 'Cotonou',
            'status' => 1,
        ]);
    }

    private function colisDe(Merchant $marchand): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-' . $marchand->id,
            'customer_name' => 'Client de ' . $marchand->business_name,
            'customer_phone' => '0022997123456',
            'customer_address' => 'Rue 12, Cotonou',
            'status' => 1,
        ]);
    }

    private function rechargeDe(Merchant $marchand): Wallet
    {
        return Wallet::forceCreate([
            'company_id' => $marchand->company_id,
            'user_id' => $marchand->user_id,
            'merchant_id' => $marchand->id,
            'source' => 'Wallet Recharge',
            'amount' => 25000,
        ]);
    }
}
