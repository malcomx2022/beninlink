<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\Asset;
use App\Models\Backend\Assetcategory;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Payment;
use App\Models\Backend\Support;
use App\Models\Backend\SupportChat;
use App\Models\Backend\To_do;
use App\Models\MerchantPayment;
use App\Models\User;
use App\Repositories\Asset\AssetInterface;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\HubManage\HubPayment\HubPaymentInterface;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use App\Repositories\Support\SupportInterface;
use App\Repositories\Todo\TodoInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S51 — le second identifiant sur les PORTES DE CREATION.
 *
 * Septieme lot d'affilee sur la meme forme : la ressource est neuve ou gardee,
 * et le SECOND identifiant — celui qui dit a quoi elle se rattache — arrive du
 * formulaire sans perimetre.
 *
 * Ce lot-ci est celui que le releve de S50 avait designe : onze routes ou mon
 * instrument ne reconnaissait AUCUNE garde. La lecture en a reclasse quatre
 * (elles etaient gardees autrement) et en exempte deux (elles ne portent aucun
 * identifiant de locataire). Restent les portes ci-dessous.
 *
 * ⚠️ Deux d'entre elles DEPLACENT DE L'ARGENT, et c'est la que le defaut coute :
 *
 * | Porte | Identifiant nu | Ce que le socle en faisait |
 * |---|---|---|
 * | versement marchand | `merchant` | `current_balance - amount`, plus un releve |
 * | versement marchand | `from_account` | le compte de TRESORERIE debite |
 * | versement marchand | `merchant_account` | le compte bancaire credite |
 * | versement entrepot | `from_account` | le compte de TRESORERIE debite |
 * | versement entrepot | `hub_id` | l'entrepot creancier |
 * | immobilisation | `assetcategory_id`, `hub_id` | classee et posee chez le voisin |
 * | livreur | `hub_id` | affecte a l'entrepot du voisin |
 * | tache | `user_id` | assignee a l'agent du voisin |
 * | reponse a un ticket | `support_id` | ecrite dans le fil du voisin |
 */
class CreationDoorScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->actingAs($this->agentDe(settings()->id));
    }

    /* ─────────────── les deux portes qui deplacent de l'argent ─────────────── */

    /**
     * ⚠️ Le pire du lot : un versement saisi chez nous DEBITAIT le solde d'un
     * marchand d'une autre societe, en lui attachant un releve a notre nom.
     */
    public function test_a_merchant_payment_never_debits_a_merchant_of_another_company(): void
    {
        $sien  = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $avant = (float) $sien->current_balance;

        $this->assertFalse(
            (bool) app(PaymentInterface::class)->store($this->requeteVersement(['merchant' => $sien->id])),
            'un versement a debite le marchand d\'une autre societe',
        );

        $this->assertSame($avant, (float) $sien->fresh()->current_balance);
        $this->assertSame(0, MerchantStatement::where('merchant_id', $sien->id)->count());
        $this->assertSame(0, Payment::count());
    }

    /** ⚠️ Le compte de TRESORERIE de l'autre societe, debite par le meme chemin. */
    public function test_a_merchant_payment_never_debits_the_cash_account_of_another_company(): void
    {
        $sonCompte = $this->compteDe(self::AUTRE);
        $avant     = (float) $sonCompte->balance;

        $this->assertFalse((bool) app(PaymentInterface::class)->store($this->requeteVersement([
            'from_account' => $sonCompte->id,
        ])));

        $this->assertSame($avant, (float) $sonCompte->fresh()->balance);
        $this->assertSame(0, Payment::count());
    }

    /**
     * ⚠️ Le cas que `companywise()` seul ne couvrait PAS : payer NOTRE marchand
     * sur le compte bancaire d'un AUTRE de nos marchands. Les deux sont de la
     * maison ; c'est le rattachement entre eux qui est faux (lecon de S49).
     */
    public function test_a_merchant_payment_never_pays_into_another_merchants_bank_account(): void
    {
        $mien       = $this->marchandDe(settings()->id, 'a');
        $unAutre    = $this->marchandDe(settings()->id, 'b');
        $sonCompte  = $this->compteBancaireDe($unAutre);

        $this->assertFalse(
            (bool) app(PaymentInterface::class)->store($this->requeteVersement([
                'merchant'         => $mien->id,
                'merchant_account' => $sonCompte->id,
            ])),
            'un versement a ete dirige vers le compte bancaire d\'un autre marchand',
        );

        $this->assertSame(0, Payment::count());

        // Controle negatif : sur SON propre compte, le versement passe.
        $leSien = $this->compteBancaireDe($mien);
        $this->assertTrue((bool) app(PaymentInterface::class)->store($this->requeteVersement([
            'merchant'         => $mien->id,
            'merchant_account' => $leSien->id,
        ])));
        $this->assertSame(1, Payment::count());
    }

    /** ⚠️ Le versement d'entrepot debitait lui aussi la tresorerie d'en face. */
    public function test_a_hub_payment_never_debits_the_cash_account_of_another_company(): void
    {
        $sonCompte = $this->compteDe(self::AUTRE);
        $avant     = (float) $sonCompte->balance;

        $this->assertFalse((bool) app(HubPaymentInterface::class)->store(new Request([
            'hub_id' => $this->hubDe(settings()->id)->id,
            'from_account' => $sonCompte->id,
            'isprocess' => 1, 'amount' => 1000, 'transaction_id' => 'TR-S51', 'description' => 'S51',
        ])));

        $this->assertSame($avant, (float) $sonCompte->fresh()->balance);
        $this->assertSame(0, HubPayment::count());
    }

    /** L'entrepot creancier du versement, lui aussi nomme par le formulaire. */
    public function test_a_hub_payment_never_credits_a_hub_of_another_company(): void
    {
        $sonHub = $this->hubDe(self::AUTRE);

        $this->assertFalse((bool) app(HubPaymentInterface::class)->store(new Request([
            'hub_id' => $sonHub->id, 'amount' => 1000, 'description' => 'S51',
        ])));

        $this->assertSame(0, HubPayment::count());

        // Controle negatif : notre entrepot passe.
        $this->assertTrue((bool) app(HubPaymentInterface::class)->store(new Request([
            'hub_id' => $this->hubDe(settings()->id)->id, 'amount' => 1000, 'description' => 'S51',
        ])));
        $this->assertSame(1, HubPayment::count());
    }

    /* ─────────────────────── les portes de rattachement ────────────────────── */

    /** L'immobilisation etait CLASSEE dans le catalogue du voisin. */
    public function test_an_asset_is_never_filed_under_another_companys_category(): void
    {
        $saCategorie = Assetcategory::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Categorie voisine', 'position' => 1,
        ]);

        $this->assertFalse((bool) app(AssetInterface::class)->store($this->requeteImmobilisation([
            'assetcategory_id' => $saCategorie->id,
        ])));

        $this->assertSame(0, Asset::count());

        // Controle positif : notre propre categorie passe. Sans lui, un refus
        // du depot pour n'importe quelle autre raison validerait le test.
        $notre = Assetcategory::forceCreate([
            'company_id' => settings()->id, 'title' => 'Notre categorie', 'position' => 1,
        ]);
        $this->assertTrue((bool) app(AssetInterface::class)->store($this->requeteImmobilisation([
            'assetcategory_id' => $notre->id,
        ])));
        $this->assertSame(1, Asset::count());
    }

    /** ... et POSEE dans son entrepot. */
    public function test_an_asset_is_never_placed_in_another_companys_hub(): void
    {
        $this->assertFalse((bool) app(AssetInterface::class)->store($this->requeteImmobilisation([
            'hub_id' => $this->hubDe(self::AUTRE)->id,
        ])));

        $this->assertSame(0, Asset::count());

        // Controle negatif : chez nous, l'immobilisation est bien creee.
        $this->assertTrue((bool) app(AssetInterface::class)->store($this->requeteImmobilisation([
            'hub_id' => $this->hubDe(settings()->id)->id,
        ])));
        $this->assertSame(1, Asset::count());
    }

    /** Le livreur etait affecte a l'entrepot du voisin. */
    public function test_a_deliveryman_is_never_attached_to_another_companys_hub(): void
    {
        $this->assertFalse((bool) app(DeliveryManInterface::class)->store(new Request([
            'hub_id' => $this->hubDe(self::AUTRE)->id,
            'name' => 'Livreur S51', 'mobile' => '0022997000051', 'email' => 'livreur.s51@example.test',
            'password' => 'secret', 'address' => 'Cotonou', 'status' => Status::ACTIVE,
            'salary' => '', 'delivery_charge' => '', 'pickup_charge' => '', 'return_charge' => '',
            'opening_balance' => '', 'lat' => '', 'long' => '',
        ])));

        $this->assertSame(0, DeliveryMan::where('company_id', settings()->id)->count());
        $this->assertNull(User::firstWhere('email', 'livreur.s51@example.test'));
    }

    /** La tache etait ASSIGNEE a l'agent du voisin, qui la voyait. */
    public function test_a_todo_is_never_assigned_to_a_user_of_another_company(): void
    {
        $sonAgent = $this->agentDe(self::AUTRE);

        $this->assertFalse((bool) app(TodoInterface::class)->store(new Request([
            'user_id' => $sonAgent->id, 'title' => 'Tache S51', 'description' => 'S51', 'date' => date('Y-m-d'),
        ])));

        $this->assertSame(0, To_do::count());

        // Controle negatif : notre agent recoit bien la tache.
        $this->assertTrue((bool) app(TodoInterface::class)->store(new Request([
            'user_id' => $this->agentDe(settings()->id)->id,
            'title' => 'Tache S51', 'description' => 'S51', 'date' => date('Y-m-d'),
        ])));
        $this->assertSame(1, To_do::count());
    }

    /**
     * ⚠️ Quatrieme passage dans `SupportRepository`, et `reply()` avait survecu
     * aux trois precedents. Le message atterrissait dans le fil du ticket d'un
     * autre transporteur, signe de notre identifiant.
     */
    public function test_a_support_reply_never_lands_in_another_companys_ticket(): void
    {
        $sonTicket = $this->ticketDe(self::AUTRE);

        $this->assertFalse((bool) app(SupportInterface::class)->reply(new Request([
            'support_id' => $sonTicket->id, 'message' => 'Message indiscret S51',
        ])));

        $this->assertSame(0, SupportChat::where('support_id', $sonTicket->id)->count());

        // Controle negatif : sur notre propre ticket, la reponse s'ecrit.
        $notre = $this->ticketDe(settings()->id);
        $this->assertTrue((bool) app(SupportInterface::class)->reply(new Request([
            'support_id' => $notre->id, 'message' => 'Reponse legitime S51',
        ])));
        $this->assertSame(1, SupportChat::where('support_id', $notre->id)->count());
    }

    /* ───── les trois deja gardees : on les MESURE, on ne les corrige pas ───── */

    /**
     * `todoComplete` et `todoProcessing` portaient deja leur garde, mais sous
     * une forme que mon instrument de S50 ne reconnaissait pas : une comparaison
     * a la main (`company_id == settings()->id`) au lieu de `companywise()`.
     * Elles etaient donc comptees « sans garde » a tort.
     *
     * > Un instrument qui cherche une FORME ne trouve pas une REGLE.
     */
    public function test_a_todo_of_another_company_is_never_completed(): void
    {
        $sienne = $this->tacheDe(self::AUTRE);

        $this->assertFalse((bool) app(TodoInterface::class)->todoComplete($sienne->id, new Request(['note' => 'S51'])));
        $this->assertNull($sienne->fresh()->note);

        $notre = $this->tacheDe(settings()->id);
        $this->assertTrue((bool) app(TodoInterface::class)->todoComplete($notre->id, new Request(['note' => 'S51'])));
        $this->assertSame('S51', $notre->fresh()->note);
    }

    /** Le meme, sur la mise en cours. */
    public function test_a_todo_of_another_company_is_never_set_processing(): void
    {
        $sienne = $this->tacheDe(self::AUTRE);

        $this->assertFalse((bool) app(TodoInterface::class)->todoProcessing($sienne->id, new Request(['note' => 'S51'])));
        $this->assertNull($sienne->fresh()->note);

        $notre = $this->tacheDe(settings()->id);
        $this->assertTrue((bool) app(TodoInterface::class)->todoProcessing($notre->id, new Request(['note' => 'S51'])));
        $this->assertSame('S51', $notre->fresh()->note);
    }

    /**
     * ⚠️ Le panneau MARCHAND gardait deja sa reponse aux tickets, alors que le
     * back-office ne la gardait pas. C'est l'inverse de l'asymetrie supposee en
     * S47 — et la raison pour laquelle on mesure les deux cotes plutot que d'en
     * deduire un depuis l'autre.
     */
    public function test_a_merchant_panel_reply_never_lands_in_another_companys_ticket(): void
    {
        $sonTicket = $this->ticketDe(self::AUTRE);

        $this->assertFalse((bool) app(\App\Repositories\MerchantPanel\Support\SupportInterface::class)
            ->reply(new Request(['support_id' => $sonTicket->id, 'message' => 'S51'])));

        $this->assertSame(0, SupportChat::where('support_id', $sonTicket->id)->count());
    }


    /* ────────── la lecture de solde, EN AMONT du depot (controleur) ────────── */

    /**
     * ⚠️ Ce cas separe deux gardes que tout confond a l'oeil : le depot refuse
     * bien l'ecriture, mais le CONTROLEUR lisait le solde du compte d'en face
     * AVANT lui, et branchait dessus.
     *
     * Pour les distinguer, le compte etranger est volontairement PLUS PAUVRE
     * que le montant demande. Sans la garde du controleur, le socle compare
     * 1 000 a 10, conclut « solde de messagerie insuffisant » et repart — un
     * message qui REVELE l'etat du compte d'une autre societe. Avec elle, le
     * refus ne dit rien de ce compte.
     *
     * C'est donc le MESSAGE qui porte la preuve, pas l'absence d'ecriture :
     * l'ecriture, le depot l'empechait deja.
     */
    public function test_a_hub_payment_never_reads_the_balance_of_another_companys_account(): void
    {
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $sonCompte = $this->compteDe(self::AUTRE);
        $sonCompte->balance = 10;
        $sonCompte->save();

        $this->actingAs($this->agentAvecDroit('hub_payment_create'))
            ->post(self::HOTE . '/admin/request/hub/payment/store', [
                'hub_id' => $this->hubDe(settings()->id)->id,
                'from_account' => $sonCompte->id,
                'isprocess' => 1, 'amount' => 1000,
                'transaction_id' => 'TR-S51', 'description' => 'S51',
            ]);

        // ⚠️ ANCRAGE : sans lui, ce test serait creux. `assertStringNotContains`
        // passe sur une chaine VIDE — donc aussi quand la requete n'atteint
        // jamais le controleur (403 d'une permission mal nommee, 302 d'un
        // abonnement absent). On exige donc D'ABORD la preuve que le
        // controleur a bien tourne et refuse.
        $this->assertStringContainsString(
            __('hub_payment.error_msg'),
            $this->messagesToastr(),
            'la requete n\'a pas atteint le controleur : le test ne prouverait rien',
        );
        $this->assertStringNotContainsString(
            __('hub_payment.not_enough_courier_balance'),
            $this->messagesToastr(),
            'le controleur a compare le montant au solde du compte d\'une autre societe',
        );
        $this->assertSame(0, HubPayment::count());
    }

    /** Le meme, sur le solde d'un MARCHAND d'une autre societe. */
    public function test_a_merchant_payment_never_reads_the_balance_of_another_companys_merchant(): void
    {
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $sien = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $sien->current_balance = 10;
        $sien->save();

        // `merchant_account` est OBLIGATOIRE cote validation : sans lui la
        // requete meurt avant le controleur, et l'ancrage ci-dessous le dit.
        // On fournit donc le compte bancaire DU marchand vise — c'est aussi la
        // forme la plus realiste de l'abus.
        $sonCompte = $this->compteBancaireDe($sien);

        $this->actingAs($this->agentAvecDroit('payment_create'))
            ->post(self::HOTE . '/admin/payment/store', [
                'merchant' => $sien->id,
                'merchant_account' => $sonCompte->id,
                'from_account' => $this->compteDe(settings()->id)->id,
                'isprocess' => 1, 'amount' => 1000,
                'transaction_id' => 'TR-S51', 'description' => 'S51', 'date' => date('Y-m-d'),
            ]);

        // Meme ancrage : c'est ce test-ci qui etait creux au premier jet. La
        // route exige `payment_create`, pas `merchant_payment_create` ; mon
        // agent recevait un 403 et l'assertion passait sur du vide. Le
        // sabotage du controleur l'a dit en restant VERT.
        $this->assertStringContainsString(
            __('merchantmanage.error_msg'),
            $this->messagesToastr(),
            'la requete n\'a pas atteint le controleur : le test ne prouverait rien',
        );
        $this->assertStringNotContainsString(
            __('merchantmanage.not_enough_merchant_balance'),
            $this->messagesToastr(),
            'le controleur a compare le montant au solde d\'un marchand d\'une autre societe',
        );
        $this->assertSame(0, Payment::count());
    }

    private function tacheDe(int $societe): To_do
    {
        return To_do::forceCreate([
            'company_id' => $societe, 'user_id' => $this->agentDe($societe)->id,
            'title' => 'Tache S51 ' . $societe, 'description' => 'S51', 'date' => date('Y-m-d'),
        ]);
    }

    /** Le texte des messages Toastr deposes en session par la requete. */
    private function messagesToastr(): string
    {
        return json_encode(session('toastr::messages') ?? [], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function agentAvecDroit(string $droit): User
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = [$droit];
        $agent->save();

        return $agent;
    }

    /* ────────────────────────────── fixtures ───────────────────────────────── */

    private function requeteVersement(array $champs): Request
    {
        $marchand = $champs['merchant'] ?? $this->marchandDe(settings()->id, 'defaut')->id;

        return new Request($champs + [
            'merchant' => $marchand,
            'from_account' => $this->compteDe(settings()->id)->id,
            'merchant_account' => '',
            'isprocess' => 1, 'amount' => 1000, 'transaction_id' => 'TR-S51',
            'description' => 'Versement S51', 'date' => date('Y-m-d'),
        ]);
    }

    private function requeteImmobilisation(array $champs): Request
    {
        // ⚠️ `hub_id` par defaut vaut NOTRE entrepot, et non `''`. Avec `''`, la
        // contrainte de cle etrangere refusait l'insert : le depot rendait
        // `false` POUR LA MAUVAISE RAISON, et le test de la categorie passait
        // sans jamais exercer sa garde. C'est le sabotage qui l'a dit.
        return new Request($champs + [
            'name' => 'Immobilisation S51', 'assetcategory_id' => '',
            'hub_id' => $this->hubDe(settings()->id)->id,
            'supplyer_name' => 'Fournisseur', 'quantity' => 1, 'warranty' => 12,
            'invoice_no' => 'F-S51', 'amount' => 50000, 'description' => 'S51',
        ]);
    }

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S51';
        $agent->email = 'agent.s51.' . $societe . '.' . User::count() . '@example.test';
        $agent->mobile = '0022997510' . $societe . User::count();
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe, string $suffixe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand S51 ' . $suffixe;
        $utilisateur->email = 'marchand.s51.' . $suffixe . '.' . $societe . '@example.test';
        $utilisateur->mobile = '0022997520' . $societe . ord($suffixe[0]);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $utilisateur->id,
            'business_name' => 'PME S51 ' . $suffixe, 'current_balance' => 100000,
            'opening_balance' => 100000, 'status' => Status::ACTIVE,
        ]);
    }

    private function compteBancaireDe(Merchant $marchand): MerchantPayment
    {
        return MerchantPayment::forceCreate([
            'merchant_id' => $marchand->id, 'payment_method' => 1,
            'bank_name' => 'Banque S51', 'holder_name' => 'Titulaire',
            'account_no' => 'CB-' . $marchand->id, 'branch_name' => 'Cotonou',
        ]);
    }

    private function compteDe(int $societe): Account
    {
        return Account::firstWhere('account_no', 'CPT-S51-' . $societe) ?? Account::forceCreate([
            'company_id' => $societe, 'balance' => 500000,
            'account_holder_name' => 'Titulaire ' . $societe, 'account_no' => 'CPT-S51-' . $societe,
        ]);
    }

    private function hubDe(int $societe): Hub
    {
        return Hub::firstWhere('name', 'Entrepot S51 ' . $societe) ?? Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot S51 ' . $societe,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);
    }

    private function ticketDe(int $societe): Support
    {
        $auteur = $this->agentDe($societe);

        return Support::forceCreate([
            'user_id' => $auteur->id, 'service' => 'Technique', 'priority' => 'Haute',
            'subject' => 'Ticket S51 ' . $societe, 'description' => 'S51', 'date' => date('Y-m-d'),
        ]);
    }
}
