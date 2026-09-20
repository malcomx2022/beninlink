<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Role;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S40 — les aides AJAX des colis, première passe sur l'arriéré de S38.
 *
 * L'arriéré ouvert par le filet des identifiants de corps compte 90 routes,
 * dont **27 sous `POST admin/parcel/`**. Elles ne se valent pas : les
 * transitions de statut ont été gardées au fil des passes précédentes, mais les
 * **aides AJAX** — listes déroulantes, recherches, bascules — n'ont jamais été
 * touchées. C'est là que la lecture nue avait survécu, et c'est ce sous-groupe
 * que cette passe prouve.
 *
 * | Route | Ce qu'elle rendait à un identifiant étranger |
 * |---|---|
 * | `parcel/merchant/shops` | nom, téléphone, adresse des boutiques de n'importe quel marchand |
 * | `parcel/priority/update` | une **écriture** : la priorité du colis d'en face |
 * | `parcel/received-warehouse-hub-selected` | les entrepôts de **tous** les transporteurs |
 * | `parcel/transfer-hub` | l'évènement du colis d'en face **et** tous les entrepôts |
 * | `transertohub-selected-hub` | le nom de l'entrepôt d'un colis étranger |
 * | `parcel/deliveryman/search` | le **nom** du livreur affecté au colis d'en face |
 *
 * ⚠️ **Deux modèles ne peuvent pas porter `companywise()`.** `merchant_shops`
 * n'a aucune colonne `company_id` — c'est le marchand qui rattache la boutique
 * à une société ; `parcel_events` non plus — c'est le colis. Un `companywise()`
 * sur ces modèles n'existe pas et ne peut pas exister : le périmètre passe par
 * une jointure. C'est ce qui rendait ces points faciles à manquer en cherchant
 * l'absence d'un `companywise()`.
 *
 * ⚠️ **Le jumeau du panneau marchand n'a pas le même périmètre.** Au
 * back-office, l'agent voit les boutiques de sa société. Dans le panneau
 * marchand, s'arrêter à la société laisserait un marchand lire celles de son
 * **voisin chez le même transporteur**. L'identifiant envoyé par le formulaire
 * n'y décide donc de rien : c'est la session. Le test l'inscrit dans les deux
 * sens, parce que c'est exactement le genre d'écart qu'une correction
 * mécanique — le même `companywise()` partout — aurait introduit.
 */
class ParcelAjaxHelpersScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;
    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private Merchant $monMarchand;
    private Merchant $sonMarchand;
    private Parcel $monColis;
    private Parcel $sonColis;
    private Hub $monEntrepot;
    private Hub $sonEntrepot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $this->monMarchand = $this->marchandDe(settings()->id, 'mien');
        $this->sonMarchand = $this->marchandDe(self::AUTRE, 'sien');

        $this->monColis = $this->colisDe($this->monMarchand);
        $this->sonColis = $this->colisDe($this->sonMarchand);

        $this->monEntrepot = Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Entrepot maison', 'status' => 1,
        ]);
        $this->sonEntrepot = Hub::forceCreate([
            'company_id' => self::AUTRE, 'name' => 'Entrepot du voisin', 'status' => 1,
        ]);

        $this->actingAs($this->agentDe(settings()->id));
    }

    /* ─────────────────────── les boutiques ──────────────────────────────── */

    public function test_the_shops_helper_never_returns_another_companys_merchant(): void
    {
        $maBoutique = $this->boutiqueDe($this->monMarchand, 'Boutique maison');
        $saBoutique = $this->boutiqueDe($this->sonMarchand, 'Boutique du voisin');

        $reponse = $this->withHeaders(self::AJAX)
            ->post(self::HOTE . '/admin/parcel/merchant/shops', ['id' => $this->sonMarchand->id, 'shop' => 'true']);

        $reponse->assertOk();
        $this->assertStringNotContainsString('Boutique du voisin', $reponse->getContent(),
            'la boutique d\'un marchand d\'une autre société est rendue');

        // Contrôle négatif : sur mon marchand, l'aide rend bien la boutique.
        $mienne = $this->withHeaders(self::AJAX)
            ->post(self::HOTE . '/admin/parcel/merchant/shops', ['id' => $this->monMarchand->id, 'shop' => 'true']);

        $this->assertStringContainsString('Boutique maison', $mienne->getContent());
        $this->assertNotNull($maBoutique->fresh());
        $this->assertNotNull($saBoutique->fresh());
    }

    /** La branche unitaire de la même aide : `find()` sur l'identifiant de boutique. */
    public function test_the_shops_helper_never_returns_another_companys_shop_by_id(): void
    {
        $saBoutique = $this->boutiqueDe($this->sonMarchand, 'Boutique du voisin');
        $maBoutique = $this->boutiqueDe($this->monMarchand, 'Boutique maison');

        $reponse = $this->withHeaders(self::AJAX)
            ->post(self::HOTE . '/admin/parcel/merchant/shops', ['id' => $saBoutique->id]);

        $this->assertStringNotContainsString('Boutique du voisin', $reponse->getContent());

        $mienne = $this->withHeaders(self::AJAX)
            ->post(self::HOTE . '/admin/parcel/merchant/shops', ['id' => $maBoutique->id]);

        $this->assertStringContainsString('Boutique maison', $mienne->getContent());
    }

    /**
     * Le jumeau du panneau marchand, où le périmètre est la **session**, pas la
     * société : les deux marchands ci-dessous sont chez le même transporteur.
     */
    public function test_the_merchant_panel_shops_helper_is_scoped_to_the_signed_in_merchant(): void
    {
        $voisin = $this->marchandDe(settings()->id, 'voisin');

        $this->boutiqueDe($this->monMarchand, 'Boutique a moi');
        $this->boutiqueDe($voisin, 'Boutique du voisin de palier');

        $this->actingAs(User::find($this->monMarchand->user_id));

        $reponse = $this->withHeaders(self::AJAX)
            ->post(self::HOTE . '/merchant/parcel/merchant/shops', ['id' => $voisin->id, 'shop' => 'true']);

        $this->assertStringNotContainsString('Boutique du voisin de palier', $reponse->getContent(),
            'un marchand lit les boutiques d\'un autre marchand de la même société');

        // Contrôle négatif : les siennes lui sont bien rendues — et ce, quel que
        // soit l'identifiant envoyé, puisque c'est la session qui décide.
        $this->assertStringContainsString('Boutique a moi', $reponse->getContent());
    }

    /* ──────────────────── la bascule de priorité (écriture) ─────────────── */

    public function test_the_priority_toggle_refuses_another_companys_parcel(): void
    {
        $prioriteDorigine = $this->sonColis->priority_type_id;

        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => $this->sonColis->id, 'priority' => 1])
            ->assertNotFound();

        $this->assertSame((int) $prioriteDorigine, (int) $this->sonColis->fresh()->priority_type_id,
            'la priorité du colis d\'une autre société a été réécrite');

        // Contrôle négatif : sur le mien, la bascule opère vraiment. Sans cette
        // moitié, un 404 rendu par la tenancy passerait pour la preuve.
        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => $this->monColis->id, 'priority' => 1])
            ->assertOk();

        $this->assertSame(2, (int) $this->monColis->fresh()->priority_type_id);
    }

    /** Et un identifiant qui n'existe nulle part rend 404, pas 500. */
    public function test_the_priority_toggle_answers_404_on_an_unknown_parcel(): void
    {
        $this->post(self::HOTE . '/admin/parcel/priority/update', ['id' => 999999, 'priority' => 1])
            ->assertNotFound();
    }

    /* ─────────────────────────── les entrepôts ──────────────────────────── */

    public function test_the_warehouse_dropdown_only_lists_my_own_hubs(): void
    {
        $reponse = $this->post(self::HOTE . '/admin/parcel/received-warehouse-hub-selected', []);

        $this->assertStringNotContainsString('Entrepot du voisin', $reponse->getContent(),
            'la liste déroulante rend les entrepôts d\'un autre transporteur');
        $this->assertStringContainsString('Entrepot maison', $reponse->getContent());
    }

    /** La même liste, dans la branche « un entrepôt est déjà choisi ». */
    public function test_the_warehouse_dropdown_stays_scoped_when_a_hub_is_preselected(): void
    {
        $reponse = $this->post(self::HOTE . '/admin/parcel/received-warehouse-hub-selected', [
            'hub_id' => $this->monEntrepot->id,
        ]);

        $this->assertStringNotContainsString('Entrepot du voisin', $reponse->getContent());
        $this->assertStringContainsString('Entrepot maison', $reponse->getContent());
    }

    public function test_the_transfer_hub_helper_leaks_neither_the_event_nor_the_other_hubs(): void
    {
        // ⚠️ Le SECOND entrepôt du voisin est ce qui donne sa valeur au test.
        // Avec le défaut présent, l'aide lisait bien l'évènement du colis d'en
        // face — et excluait donc `sonEntrepot` de la liste, par son
        // `whereNotIn`. Une assertion portant sur ce seul entrepôt passait au
        // vert **sans rien prouver** : c'est le sabotage qui l'a montré. Celui
        // ci-dessous n'est exclu par rien.
        $sonAutreEntrepot = Hub::forceCreate([
            'company_id' => self::AUTRE, 'name' => 'Second entrepot du voisin', 'status' => 1,
        ]);

        $this->evenementSur($this->sonColis, ParcelStatus::RECEIVED_WAREHOUSE, $this->sonEntrepot->id);
        $this->evenementSur($this->monColis, ParcelStatus::RECEIVED_WAREHOUSE, $this->monEntrepot->id);

        $reponse = $this->post(self::HOTE . '/admin/parcel/transfer-hub', ['parcel_id' => $this->sonColis->id]);

        $reponse->assertOk();
        $this->assertStringNotContainsString('Second entrepot du voisin', $reponse->getContent(),
            'les entrepôts d\'un autre transporteur sont rendus');
        $this->assertNotNull($sonAutreEntrepot->fresh());

        // Contrôle négatif : sur mon colis, l'aide fait son travail — elle
        // propose mes entrepôts, sauf celui où le colis se trouve déjà.
        $autreDesMiens = Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Second entrepot maison', 'status' => 1,
        ]);

        $mienne = $this->post(self::HOTE . '/admin/parcel/transfer-hub', ['parcel_id' => $this->monColis->id]);

        $this->assertStringContainsString('Second entrepot maison', $mienne->getContent());
        $this->assertStringNotContainsString('Entrepot maison<', $mienne->getContent(),
            'l\'entrepôt où le colis se trouve déjà est encore proposé');
        $this->assertNotNull($autreDesMiens->fresh());
    }

    /** Un colis inconnu ne doit pas faire tomber l'aide : un vide, pas un 500. */
    public function test_the_transfer_hub_helper_survives_an_unknown_parcel(): void
    {
        $this->post(self::HOTE . '/admin/parcel/transfer-hub', ['parcel_id' => 999999])
            ->assertOk();
    }

    public function test_the_selected_hub_helper_never_names_another_companys_hub(): void
    {
        $this->sonColis->hub_id = $this->sonEntrepot->id;
        $this->sonColis->save();

        $this->monColis->hub_id = $this->monEntrepot->id;
        $this->monColis->save();

        $reponse = $this->post(self::HOTE . '/admin/transertohub-selected-hub', ['parcel_id' => $this->sonColis->id]);

        $this->assertStringNotContainsString('Entrepot du voisin', $reponse->getContent());

        $mienne = $this->post(self::HOTE . '/admin/transertohub-selected-hub', ['parcel_id' => $this->monColis->id]);

        $this->assertStringContainsString('Entrepot maison', $mienne->getContent());
    }

    /* ─────────────────────── la recherche de livreur ────────────────────── */

    public function test_the_deliveryman_search_never_names_the_agent_of_another_companys_parcel(): void
    {
        $sonLivreur = $this->livreurDe(self::AUTRE, 'Livreur du voisin');
        $monLivreur = $this->livreurDe(settings()->id, 'Livreur maison');

        $this->evenementSur($this->sonColis, ParcelStatus::DELIVERY_MAN_ASSIGN, null, $sonLivreur->id);
        $this->evenementSur($this->monColis, ParcelStatus::DELIVERY_MAN_ASSIGN, null, $monLivreur->id);

        $reponse = $this->post(self::HOTE . '/admin/parcel/deliveryman/search', [
            'single' => 1,
            'parcel_id' => $this->sonColis->id,
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ]);

        $reponse->assertOk();
        $this->assertStringNotContainsString('Livreur du voisin', $reponse->getContent(),
            'le nom du livreur affecté au colis d\'une autre société est rendu');

        // Contrôle négatif : sur mon colis, l'aide nomme bien mon livreur.
        $mienne = $this->post(self::HOTE . '/admin/parcel/deliveryman/search', [
            'single' => 1,
            'parcel_id' => $this->monColis->id,
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ]);

        $this->assertStringContainsString('Livreur maison', $mienne->getContent());
    }

    /** Un colis sans évènement rendait un 500 sur les deux branches. */
    public function test_the_deliveryman_search_survives_a_parcel_without_any_event(): void
    {
        $this->post(self::HOTE . '/admin/parcel/deliveryman/search', [
            'single' => 1,
            'parcel_id' => $this->monColis->id,
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ])->assertOk();
    }

    /* ─────────────────────────── outillage ──────────────────────────────── */

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent aides';
        $agent->email = 'agent.aides.' . $societe . '@example.test';
        $agent->mobile = '00229971000' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->role_id = Role::where('company_id', $societe)->value('id');
        $agent->permissions = ['parcel_read', 'parcel_create', 'parcel_update', 'parcel_status_update'];
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe, string $suffixe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand ' . $suffixe;
        $utilisateur->email = 'marchand.aides.' . $suffixe . '@example.test';
        $utilisateur->mobile = '0022997200' . strlen($suffixe) . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME ' . $suffixe,
            'current_balance' => 0,
        ]);
    }

    private function boutiqueDe(Merchant $marchand, string $nom): MerchantShops
    {
        return MerchantShops::forceCreate([
            'merchant_id' => $marchand->id,
            'name' => $nom,
            'contact_no' => '0022997000000',
            'address' => 'Cotonou',
            'status' => Status::ACTIVE,
            'default_shop' => Status::ACTIVE,
        ]);
    }

    private function livreurDe(int $societe, string $nom): DeliveryMan
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = $nom;
        $utilisateur->email = 'livreur.aides.' . $societe . '@example.test';
        $utilisateur->mobile = '0022997300' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::DELIVERYMAN;
        $utilisateur->unique_id = 'L-AIDE-' . $societe;
        $utilisateur->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 500,
            'pickup_charge' => 200,
            'return_charge' => 300,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    private function colisDe(Merchant $marchand): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-AIDE-' . $marchand->company_id . '-' . uniqid(),
            'customer_name' => 'Client de la societe ' . $marchand->company_id,
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => ParcelStatus::PENDING,
            'priority_type_id' => 1,
        ]);
    }

    private function evenementSur(Parcel $colis, int $statut, ?int $entrepot = null, ?int $livreur = null): void
    {
        $evenement = new ParcelEvent();
        $evenement->parcel_id = $colis->id;
        $evenement->parcel_status = $statut;
        $evenement->hub_id = $entrepot;
        $evenement->delivery_man_id = $livreur;
        $evenement->note = 'fixture';
        $evenement->created_by = auth()->id();
        $evenement->save();
    }
}
