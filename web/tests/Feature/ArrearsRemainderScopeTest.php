<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\Backend\PushNotification;
use App\Models\Backend\Wallet;
use App\Models\MerchantPayment;
use App\Models\User;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\PushNotification\PushNotificationInterface;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S52 — le reste de l'arriere du filet S38, et ce qu'il cachait.
 *
 * Huitieme lot sur la meme forme. Mais les vingt routes restantes ne sont pas
 * de la meme NATURE que les precedentes : la plupart sont des aides AJAX, qui
 * ne modifient rien. On pourrait les croire sans enjeu.
 *
 * ⚠️ Trois d'entre elles rendaient des donnees qu'un concurrent paierait :
 *
 * | Aide AJAX | Ce qu'elle rendait d'une AUTRE societe |
 * |---|---|
 * | `get-merchant-cod` | la grille de frais de contre-remboursement d'un marchand |
 * | `merchant/account` | titulaire, banque, **numero de compte**, agence, mobile |
 * | `salary/search-account` | le **montant du salaire** d'un employe |
 *
 * Et trois ecritures atteignaient un tiers d'une autre societe :
 *
 * | Ecriture | Ce qu'elle faisait |
 * |---|---|
 * | recharge de portefeuille | **credite** `wallet_balance` et **envoie un SMS** |
 * | notification poussee | **pousse** sur l'appareil du destinataire |
 * | creation/inscription marchand | le rattache a l'**entrepot** du voisin |
 *
 * ⚠️ La distinction qui a structure la lecture de ce lot : un identifiant
 * etranger utilise comme FILTRE sur une requete deja scopee ne fuit rien — la
 * jointure ne rend simplement aucune ligne. Il n'est dangereux que quand il
 * sert a ALLER CHERCHER. C'est pourquoi les trois recherches de colis sont
 * saines et que `get-merchant-cod` ne l'etait pas, alors que les quatre se
 * ressemblent a l'oeil.
 *
 * ⚠️ **Deux routes restent a l'arriere, et ce fichier dit pourquoi.**
 * `parcel/partial-delivered/cancel` et `parcel/return-received-by-merchant`
 * portent leur garde depuis S45. Je croyais leur portee prouvee par
 * `PartialDeliveryAccountingTest` et `DeliveryCancellationAccountingTest`,
 * d'apres un releve anterieur. Verification au sabotage, puis sur la suite
 * ENTIERE : **aucun test ne tombe**. L'attribution etait fausse.
 *
 * J'ai alors ecrit deux cas ici — ils passaient, et le sabotage les a trouves
 * **creux** : sur un colis etranger incomplet, la transaction leve et le `catch`
 * rend `false` de toute facon. Le refus ne venait pas de la garde. Les prouver
 * demande un colis d'en face assez complet pour que le chemin NON garde
 * REUSSISSE — c'est le lot suivant, pas une ligne de plus ici.
 *
 * > Un test qui passe sans exercer sa garde est pire que pas de test : il
 * > donne une confiance que rien ne soutient. Ces deux-la sont donc retires,
 * > et leurs routes restent a l'arriere — c'est exactement ce que l'arriere
 * > veut dire.
 *
 * **Suite (S53).** Les deux routes sont depuis sorties de l'arriere : il a
 * fallu construire un colis d'en face COMPLET — livreur assigne, evenement
 * `DELIVERY_MAN_ASSIGN`, montants numeriques — pour que le chemin NON garde
 * aboutisse, et donc que la garde devienne mesurable. Voir
 * `ParcelCancelScopeTest`.
 */
class ArrearsRemainderScopeTest extends TestCase
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

    /* ───────────── les lectures qui renseignaient un concurrent ───────────── */

    /** La grille de frais COD d'un marchand : une donnee commerciale negociee. */
    public function test_the_cod_helper_never_reveals_another_companys_merchant(): void
    {
        $sien = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $sien->cod_charges = ['inside_city' => 111, 'sub_city' => 222, 'outside_city' => 333];
        $sien->save();

        $reponse = $this->ajax('/admin/get-merchant-cod', ['merchant_id' => $sien->id]);

        $this->assertStringNotContainsString('111', $reponse);
        $this->assertStringNotContainsString('222', $reponse);

        // Controle positif : notre marchand, lui, est bien rendu.
        $mien = $this->marchandDe(settings()->id, 'cod');
        $mien->cod_charges = ['inside_city' => 444, 'sub_city' => 555, 'outside_city' => 666];
        $mien->save();
        $this->assertStringContainsString('444', $this->ajax('/admin/get-merchant-cod', ['merchant_id' => $mien->id]));
    }

    /** ⚠️ Les COORDONNEES BANCAIRES des comptes de versement d'un marchand. */
    public function test_the_account_helper_never_reveals_another_companys_bank_details(): void
    {
        $sien = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $this->compteBancaireDe($sien, 'SECRET-IBAN-VOISIN');

        $reponse = $this->ajax('/admin/merchant/account', ['merchant_id' => $sien->id]);
        $this->assertStringNotContainsString('SECRET-IBAN-VOISIN', $reponse);

        // Controle positif : le compte du notre est bien propose.
        $mien = $this->marchandDe(settings()->id, 'banque');
        $this->compteBancaireDe($mien, 'MON-IBAN');
        $this->assertStringContainsString('MON-IBAN', $this->ajax('/admin/merchant/account', ['merchant_id' => $mien->id]));
    }

    /** Le MONTANT DU SALAIRE d'un employe d'une autre societe. */
    public function test_the_salary_helper_never_reveals_another_companys_payroll(): void
    {
        $sonAgent = $this->agentDe(self::AUTRE);
        SalaryGenerate::forceCreate([
            'company_id' => self::AUTRE, 'user_id' => $sonAgent->id,
            'month' => '2026-09', 'amount' => 987654, 'status' => Status::ACTIVE,
        ]);

        $reponse = $this->ajax('/admin/salary/search-account', ['user_id' => $sonAgent->id, 'month' => '2026-09']);
        $this->assertStringNotContainsString('987654', $reponse);

        // Controle positif : notre propre bulletin est bien rendu.
        $notre = $this->agentDe(settings()->id);
        SalaryGenerate::forceCreate([
            'company_id' => settings()->id, 'user_id' => $notre->id,
            'month' => '2026-09', 'amount' => 123456, 'status' => Status::ACTIVE,
        ]);
        $this->assertStringContainsString('123456', $this->ajax('/admin/salary/search-account', ['user_id' => $notre->id, 'month' => '2026-09']));
    }

    /* ─────────── les ecritures qui atteignaient un tiers etranger ─────────── */

    /**
     * ⚠️ Le pire du lot : la recharge de portefeuille ne se contentait pas
     * d'ecrire une ligne — elle CREDITE le solde du marchand et lui ENVOIE UN SMS.
     */
    public function test_a_wallet_recharge_never_credits_a_merchant_of_another_company(): void
    {
        $sien  = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $avant = (float) $sien->wallet_balance;

        $this->assertFalse((bool) app(WalletInterface::class)->adminstore(new Request([
            'merchant_id' => $sien->id, 'amount' => 50000, 'transaction_id' => 'TR-S52',
        ])), 'une recharge a credite le portefeuille d\'un marchand d\'une autre societe');

        $this->assertSame($avant, (float) $sien->fresh()->wallet_balance);
        $this->assertSame(0, Wallet::count());

        // Controle positif : notre marchand est bien recharge.
        $mien = $this->marchandDe(settings()->id, 'wallet');
        $this->assertTrue((bool) app(WalletInterface::class)->adminstore(new Request([
            'merchant_id' => $mien->id, 'amount' => 50000, 'transaction_id' => 'TR-S52',
        ])));
        $this->assertSame(50000.0, (float) $mien->fresh()->wallet_balance);
    }

    /** ⚠️ La notification n'est pas qu'enregistree : elle est POUSSEE. */
    public function test_a_push_notification_never_targets_a_user_of_another_company(): void
    {
        $this->assertFalse((bool) app(PushNotificationInterface::class)->store(new Request([
            'user_id' => $this->agentDe(self::AUTRE)->id,
            'title' => 'S52', 'description' => 'S52', 'role_id' => 'user',
        ])));

        $this->assertSame(0, PushNotification::count());
    }

    /** Le meme, sur un MARCHAND d'une autre societe. */
    public function test_a_push_notification_never_targets_a_merchant_of_another_company(): void
    {
        $this->assertFalse((bool) app(PushNotificationInterface::class)->store(new Request([
            'merchant_id' => Merchant::where('company_id', self::AUTRE)->firstOrFail()->id,
            'title' => 'S52', 'description' => 'S52', 'role_id' => 'merchant',
        ])));

        $this->assertSame(0, PushNotification::count());
    }

    /** Le marchand cree etait rattache a l'entrepot du voisin. */
    public function test_a_created_merchant_is_never_attached_to_another_companys_hub(): void
    {
        app(MerchantInterface::class)->store(new Request($this->champsMarchand([
            'hub' => $this->hubDe(self::AUTRE)->id,
        ])));

        $cree = User::firstWhere('email', 'marchand.s52@example.test');
        $this->assertTrue(blank($cree) || blank($cree->hub_id),
            'un marchand a ete rattache a l\'entrepot d\'une autre societe');

        // Controle positif : notre entrepot est bien conserve.
        $notre = $this->hubDe(settings()->id);
        app(MerchantInterface::class)->store(new Request($this->champsMarchand([
            'hub' => $notre->id, 'email' => 'marchand.s52b@example.test', 'mobile' => '0022997520099',
        ])));
        $ok = User::firstWhere('email', 'marchand.s52b@example.test');
        $this->assertSame($notre->id, $ok?->hub_id);
    }

    /** ⚠️ Et la meme porte en version PUBLIQUE : l'inscription libre-service. */
    public function test_a_public_merchant_signup_is_never_attached_to_another_companys_hub(): void
    {
        app(MerchantInterface::class)->signUpStore(new Request($this->champsMarchand([
            'hub_id' => $this->hubDe(self::AUTRE)->id,
            'full_name' => 'Inscrit S52', 'email' => 'inscrit.s52@example.test',
        ])));

        $cree = User::firstWhere('email', 'inscrit.s52@example.test');
        $this->assertTrue(blank($cree) || blank($cree->hub_id),
            'une inscription publique s\'est rattachee a l\'entrepot d\'une autre societe');
    }

    /* ───────── les deja gardees : on les MESURE, on ne les corrige pas ────── */

    /**
     * ⚠️ Le contre-exemple qui donne sa regle au lot : ici l'identifiant
     * etranger sert de FILTRE sur `Parcel::companywise()`. La requete ne rend
     * rien, et rien ne fuit — alors que la forme ressemble a `get-merchant-cod`.
     */
    public function test_the_pickup_search_never_reaches_another_companys_parcel(): void
    {
        $sien = $this->colisDe(self::AUTRE);

        $reponse = $this->ajax('/admin/assign-pickup/parcel/search', [
            'merchant_id' => $sien->merchant_id, 'tracking_id' => $sien->tracking_id,
        ]);

        $this->assertStringNotContainsString($sien->tracking_id, $reponse);
    }

    /** Le meme, sur la recherche des retours a rendre au marchand. */
    public function test_the_return_search_never_reaches_another_companys_parcel(): void
    {
        $sien = $this->colisDe(self::AUTRE, \App\Enums\ParcelStatus::RETURN_TO_COURIER);

        $reponse = $this->ajax('/admin/assign-return-to-merchant/parcel/search', [
            'merchant_id' => $sien->merchant_id, 'tracking_id' => $sien->tracking_id,
        ]);

        $this->assertStringNotContainsString($sien->tracking_id, $reponse);
    }

    /** Et sur la reception en entrepot. */
    public function test_the_hub_reception_search_never_reaches_another_companys_parcel(): void
    {
        $sonEntrepot = $this->hubDe(self::AUTRE);
        $sien = $this->colisDe(self::AUTRE, \App\Enums\ParcelStatus::TRANSFER_TO_HUB, $sonEntrepot->id);

        $reponse = $this->ajax('/admin/parcel/recived-by-hub/search', [
            'hub_id' => $sonEntrepot->id, 'track_id' => $sien->tracking_id,
        ]);

        $this->assertStringNotContainsString($sien->tracking_id, $reponse);
    }

    /** Les comptes d'un utilisateur d'entrepot : filtre sur `Account::companywise()`. */
    public function test_the_hub_user_accounts_helper_never_reveals_another_companys_account(): void
    {
        $sonAgent  = $this->agentDe(self::AUTRE);
        Account::forceCreate([
            'company_id' => self::AUTRE, 'user_id' => $sonAgent->id, 'balance' => 900000,
            'account_holder_name' => 'Titulaire voisin', 'account_no' => 'CPT-VOISIN-S52',
        ]);

        $reponse = $this->ajax('/admin/income/hub-user-accounts', ['id' => $sonAgent->id]);
        $this->assertStringNotContainsString('CPT-VOISIN-S52', $reponse);
    }

    /** La bascule d'un reglage SMS : gardee, et elle refuse. */
    public function test_an_sms_setting_of_another_company_is_never_toggled(): void
    {
        $sien = \App\Models\Backend\SmsSendSetting::forceCreate([
            'company_id' => self::AUTRE,
            'sms_send_status' => \App\Enums\SmsSendStatus::PARCEL_CREATE,
            'status' => Status::ACTIVE,
        ]);

        // ⚠️ 404 attendu, et c'est ce lot qui l'a rendu possible : le perimetre
        // tenait deja, mais l'affectation sur `null` rendait **500**. Un refus se
        // dit (S15, S30, S39), il ne plante pas — et un 500 aurait fait passer
        // l'assertion d'absence sans rien prouver.
        $this->ajax('/admin/sms-send-settings/status', ['id' => $sien->id, 'status' => Status::ACTIVE], 404);

        $this->assertSame((int) Status::ACTIVE, (int) $sien->fresh()->status,
            'le reglage SMS d\'une autre societe a ete bascule');
    }

    /** La ligne de grille tarifaire d'un autre transporteur (gardee en S34). */
    public function test_the_delivery_charge_helper_never_reveals_another_companys_grid(): void
    {
        $sienne = $this->ligneDeGrille(self::AUTRE, 777777);

        $reponse = $this->ajax('/admin/merchant/delivery-charge/info', ['delivery_charge_id' => $sienne->id]);
        $this->assertStringNotContainsString('777777', $reponse);

        $notre = $this->ligneDeGrille((int) settings()->id, 555555);
        $this->assertStringContainsString('555555', $this->ajax('/admin/merchant/delivery-charge/info', ['delivery_charge_id' => $notre->id]));
    }

    /** Le formulaire de compte de versement d'un marchand etranger (gardee en S34). */
    public function test_the_payment_method_helper_refuses_another_companys_merchant(): void
    {
        $sien = Merchant::where('company_id', self::AUTRE)->firstOrFail();

        // 404 : le refus se dit, il ne plante pas (S15, S30).
        $this->ajax('/admin/merchant/paymentmethod/change',
            ['merchant_id' => $sien->id, 'payment_method' => 'bank'], 404);
    }

    /** Les tranches de poids d'une categorie : filtre sur `DeliveryCharge::companywise()`. */
    /**
     * ⚠️ L'assertion porte sur le TITRE de la categorie, pas sur le montant :
     * la vue `deliveryWeight` ne rend que `weight` et `category->title`. Mon
     * premier jet assertait l'absence du montant — une valeur qui n'apparait
     * JAMAIS dans cette reponse, donc une assertion vraie sans rien prouver.
     * Le sabotage l'a dit en restant vert.
     */
    public function test_the_delivery_weight_helper_never_reveals_another_companys_grid(): void
    {
        $sienne = $this->ligneDeGrille(self::AUTRE, 888888);

        $reponse = $this->ajax('/admin/parcel/delivery-category', ['category_id' => $sienne->category_id]);
        $this->assertStringNotContainsString('Categorie S52 ' . self::AUTRE, $reponse);

        // Controle positif : notre propre categorie est bien proposee.
        $notre = $this->ligneDeGrille((int) settings()->id, 111111);
        $this->assertStringContainsString('Categorie S52 ' . settings()->id,
            $this->ajax('/admin/parcel/delivery-category', ['category_id' => $notre->category_id]));
    }

    /**
     * Le JUMEAU du precedent, cote panneau marchand. Garde identique, mais
     * point d'appel distinct : une garde posee dans deux methodes n'est pas
     * une garde prouvee dans les deux (lecon de S47).
     */
    public function test_the_merchant_panel_weight_helper_never_reveals_another_companys_grid(): void
    {
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $this->routesMontees = true;

        $sienne  = $this->ligneDeGrille(self::AUTRE, 999999);
        $marchand = $this->marchandDe((int) settings()->id, 'grille');

        $reponse = $this->actingAs($marchand->user)
            ->post(self::HOTE . '/merchant/parcel/delivery-category',
                ['category_id' => $sienne->category_id],
                ['X-Requested-With' => 'XMLHttpRequest']);

        $reponse->assertOk();
        $this->assertStringNotContainsString('Categorie S52 ' . self::AUTRE, $reponse->getContent());
    }

    /* ────────────────────────────── fixtures ───────────────────────────────── */

    /** Un appel AJAX authentifie sur une route de locataire, tous droits ouverts. */
    private bool $routesMontees = false;

    /**
     * Un appel AJAX authentifie sur une route de locataire.
     *
     * ⚠️ **L'ANCRAGE DU FICHIER.** Presque tous les cas ci-dessus verifient une
     * ABSENCE (`assertStringNotContainsString`), et une absence est vraie sur une
     * chaine vide — donc aussi sur une page « Acces interdit », une redirection
     * d'abonnement ou une erreur 500. Chacun de ces cas passerait au vert sans
     * avoir rien exerce. C'est exactement le piege que le sabotage de S51 avait
     * revele, et il s'est represente ici : `permissions = null` ne vaut PAS
     * « tous les droits », il vaut AUCUN droit, et les routes repondaient 403.
     *
     * On exige donc **200** avant de rendre le corps : la preuve que le
     * controleur a bien tourne.
     */
    /**
     * Le droit exige par chaque route, tel que `routes/web.php` le declare.
     * Une aide AJAX sans `hasPermission` n'a pas d'entree ici.
     */
    private const DROITS = [
        '/admin/get-merchant-cod'            => ['parcel_create'],
        '/admin/salary/search-account'       => ['salary_create'],
        '/admin/income/hub-user-accounts'    => ['income_create'],
        '/admin/sms-send-settings/status'    => ['sms_send_settings_status_change'],
        '/admin/parcel/recived-by-hub/search'=> ['parcel_status_update'],
        '/admin/merchant/delivery-charge/info'=> ['merchant_delivery_charge_create'],
        '/admin/merchant/paymentmethod/change'=> ['merchant_payment_create'],
        '/admin/parcel/delivery-category'    => ['parcel_create'],
    ];

    private function ajax(string $uri, array $donnees, int $attendu = 200): string
    {
        // Une seule fois : deux appels dans le meme test reinsereraient la ligne
        // `tenants` et violeraient sa cle unique.
        if (! $this->routesMontees) {
            $this->mountTenantRoutes();
            $this->souscrireLeLocataire();
            $this->routesMontees = true;
        }

        $agent = $this->agentDe(settings()->id);
        $agent->permissions = self::DROITS[$uri] ?? [];
        $agent->save();

        $reponse = $this->actingAs($agent)
            ->post(self::HOTE . $uri, $donnees, ['X-Requested-With' => 'XMLHttpRequest']);

        $reponse->assertStatus($attendu);

        return $reponse->getContent();
    }

    private function champsMarchand(array $champs): array
    {
        return $champs + [
            'name' => 'Marchand S52', 'full_name' => 'Marchand S52',
            'email' => 'marchand.s52@example.test', 'mobile' => '0022997520088',
            'password' => 'secret', 'address' => 'Cotonou', 'status' => Status::ACTIVE,
            'business_name' => 'PME S52', 'opening_balance' => '', 'vat' => '',
            'area' => [], 'charge' => [],
        ];
    }

    private function ligneDeGrille(int $societe, int $montant): \App\Models\Backend\DeliveryCharge
    {
        $categorie = \App\Models\Backend\Deliverycategory::forceCreate([
            'company_id' => $societe, 'title' => 'Categorie S52 ' . $societe, 'status' => Status::ACTIVE,
        ]);

        // ⚠️ `same_day`/`next_day`/`sub_city`/`outside_city` n'existent PLUS :
        // l'etape 6 de D4 les a retirees (migration du 07/09) au profit de la
        // tarification par ZONE. Le barème porte desormais `zone_id` + `amount`.
        return \App\Models\Backend\DeliveryCharge::forceCreate([
            'company_id' => $societe, 'category_id' => $categorie->id, 'weight' => 5,
            'zone_id' => $this->zoneDe($societe)->id, 'amount' => $montant,
        ]);
    }

    private function zoneDe(int $societe): \App\Models\Backend\DeliveryZone
    {
        return \App\Models\Backend\DeliveryZone::firstWhere('company_id', $societe)
            ?? \App\Models\Backend\DeliveryZone::forceCreate([
                'company_id' => $societe, 'name' => 'Zone S52 ' . $societe,
                'code' => 'S52' . $societe, 'status' => Status::ACTIVE,
            ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S52';
        $agent->email = 'agent.s52.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe, string $suffixe): Merchant
    {
        $u = new User();
        $u->company_id = $societe;
        $u->name = 'Marchand ' . $suffixe;
        $u->email = 'm.s52.' . $suffixe . '.' . $societe . '@example.test';
        $u->mobile = '00229976' . $societe . ord($suffixe[0]);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME ' . $suffixe, 'current_balance' => 0,
            'opening_balance' => 0, 'wallet_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function compteBancaireDe(Merchant $marchand, string $numero): MerchantPayment
    {
        return MerchantPayment::forceCreate([
            'merchant_id' => $marchand->id, 'payment_method' => 'bank',
            'bank_name' => 'Banque S52', 'holder_name' => 'Titulaire',
            'account_no' => $numero, 'branch_name' => 'Cotonou',
        ]);
    }

    private function hubDe(int $societe): Hub
    {
        return Hub::firstWhere('name', 'Entrepot S52 ' . $societe) ?? Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot S52 ' . $societe,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);
    }

    /**
     * ⚠️ Le statut est un PARAMETRE, et ce n'est pas cosmetique : chaque
     * recherche filtre sur LE SIEN. Un colis cree en `PENDING` ne pouvait pas
     * matcher la recherche des retours (`RETURN_TO_COURIER`) ni celle de la
     * reception (`TRANSFER_TO_HUB`) — les deux tests passaient donc sans jamais
     * exercer leur garde. C'est le sabotage qui l'a dit, en restant VERT.
     */
    private function colisDe(int $societe, ?int $statut = null, ?int $entrepot = null): \App\Models\Backend\Parcel
    {
        $marchand = $societe === (int) settings()->id
            ? $this->marchandDe($societe, 'colis')
            : Merchant::where('company_id', $societe)->firstOrFail();

        return \App\Models\Backend\Parcel::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'SUIVI-VOISIN-S52',
            'customer_name' => 'Client voisin',
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => $statut ?? \App\Enums\ParcelStatus::PENDING,
            'transfer_hub_id' => $entrepot,
            'priority_type_id' => 1,
        ]);
    }
}
