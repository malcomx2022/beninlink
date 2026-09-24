<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S57 — les lectures que l'instrument raffiné a désignées.
 *
 * S47 avait relevé « ~250 occurrences de lectures non scopées », jugé
 * l'instrument **trop bruyant pour décider**, et remis son affinage à plus tard :
 * *« il faudrait ne retenir qu'une lecture dont AUCUNE garde ne domine le
 * chemin »*. Ce lot paye cette dette.
 *
 * ## L'affinage, et ce qu'il a fallu lui apprendre
 *
 * | Instrument | Occurrences |
 * |---|---|
 * | brut (toute lecture non scopée d'un modèle `companywise`) | **~570** |
 * | + l'identifiant doit venir de la **requête** | 53 |
 * | + connaître les **aides de garde du projet** | **43** |
 *
 * Le dernier critère est celui qui rend l'instrument honnête :
 * `contrepartieHorsPerimetre()` (S48) ne contient pas la chaîne `companywise(`,
 * donc l'instrument brut **accusait** les dépôts de recette, dépense et salaire —
 * gardés depuis S48. Un instrument qui cherche une forme ne trouve pas une règle :
 * c'est la leçon de S52, reprise ici sur mon propre outil.
 *
 * ## ⚠️ La divulgation la plus grave de la série
 *
 * `ParcelRepository::parcelSearchs()` n'avait **aucun** périmètre. Elle rendait
 * les colis de **toutes** les sociétés, avec `customer_name`, `customer_phone` et
 * `customer_address` : les **données personnelles** des clients d'un transporteur
 * concurrent. Même nature que le défaut de **S28**.
 *
 * Et elle échappait aux trois filets :
 *
 * | Filet | Pourquoi il ne la voyait pas |
 * |---|---|
 * | `WebIsolationCoverageTest` | n'énumère que les routes à **paramètre d'URL** ; celle-ci n'en a pas |
 * | `BodyIdentifierCoverageTest` | n'énumère que les **écritures** (`POST/PUT/PATCH/DELETE`) ; c'est un **GET** |
 * | `WebAdminPermissionCoverageTest` | mesure les **droits**, pas le périmètre |
 *
 * ⚠️ Un **GET sans paramètre d'URL** dont l'identifiant est un terme de recherche
 * n'est couvert par aucun filet d'isolation. Il y en a une quarantaine sous les
 * trois panneaux. C'est un constat, pas une correction : il est écrit dans
 * `CARTOGRAPHIE.md` comme chantier suivant.
 *
 * ## ⚠️ Et le piège du `orWhere`
 *
 * Poser `companywise()` devant cette chaîne ne l'aurait **pas** scopée :
 * `where(A)->orWhere(B)` donne `company_id = X AND A OR B`, et le `OR` sort du
 * périmètre. Un colis d'en face correspondant sur `customer_phone` serait encore
 * rendu. Le groupe de `OR` est donc enfermé dans une fermeture — et
 * `test_the_parcel_search_scope_survives_the_or_branches` le prouve en visant
 * précisément une branche `orWhere`.
 */
class SearchAndBalanceDisclosureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    /** Le droit exigé par chaque route, tel que `routes/web.php` le déclare. */
    private const DROITS = [
        '/admin/expense/users' => ['expense_create'],
        '/admin/income/users'  => ['income_create'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    /* ─────────────── les recherches qui divulguaient ─────────────── */

    /** L'autocompletion des dépenses rendait l'annuaire du personnel d'en face. */
    public function test_the_expense_user_search_never_reveals_another_companys_staff(): void
    {
        $sien  = $this->agentDe(self::AUTRE, 'Comptable Voisin');
        $mien  = $this->agentDe((int) settings()->id, 'Comptable Maison');

        $corps = $this->ajax('/admin/expense/users', ['search' => 'Comptable']);

        $this->assertStringNotContainsString('Comptable Voisin', $corps);
        $this->assertStringContainsString('Comptable Maison', $corps,
            'le contrôle positif ne passe pas : ce cas ne prouverait rien');
        $this->assertStringNotContainsString('"id":' . $sien->id, $corps);
        $this->assertStringContainsString('"id":' . $mien->id, $corps);
    }

    /** Le jumeau, côté recettes. Deux points d'appel, deux preuves (leçon S47). */
    public function test_the_income_user_search_never_reveals_another_companys_staff(): void
    {
        $this->agentDe(self::AUTRE, 'Caissier Voisin');
        $this->agentDe((int) settings()->id, 'Caissier Maison');

        $corps = $this->ajax('/admin/income/users', ['search' => 'Caissier']);

        $this->assertStringNotContainsString('Caissier Voisin', $corps);
        $this->assertStringContainsString('Caissier Maison', $corps);
    }

    /**
     * ⚠️ Les données personnelles des clients : nom, téléphone, adresse.
     */
    public function test_the_parcel_search_never_reveals_another_companys_customers(): void
    {
        $sien = $this->colisDe(self::AUTRE, 'Cliente Voisine', '0022990000002');
        $mien = $this->colisDe((int) settings()->id, 'Cliente Maison', '0022990000001');

        $trouves = app(ParcelInterface::class)
            ->parcelSearchs(new Request(['search' => 'Cliente']))
            ->pluck('id')->all();

        $this->assertNotContains($sien->id, $trouves,
            'la recherche a rendu le colis d\'une autre société, avec son client');
        $this->assertContains($mien->id, $trouves,
            'le contrôle positif ne passe pas : ce cas ne prouverait rien');
    }

    /**
     * ⚠️ LE test du lot, et celui qu'un `companywise()` naïf aurait laissé passer.
     *
     * La recherche cherche sur six colonnes en `OR`. Si le périmètre est posé
     * devant la chaîne sans enfermer le groupe, SQL lit
     * `company_id = X AND customer_name LIKE … OR customer_phone LIKE …` : la
     * branche `orWhere` échappe au périmètre.
     *
     * Ce cas vise donc **uniquement** une branche `orWhere` — le téléphone — avec
     * un nom qui ne correspond pas. Il ne passe que si le groupe est enfermé.
     */
    public function test_the_parcel_search_scope_survives_the_or_branches(): void
    {
        // Le colis d'en face ne correspond QUE par son telephone.
        $sien = $this->colisDe(self::AUTRE, 'Nom sans rapport', '0022997654321');
        $mien = $this->colisDe((int) settings()->id, 'Autre nom', '0022991234567');

        $trouves = app(ParcelInterface::class)
            ->parcelSearchs(new Request(['search' => '0022997654321']))
            ->pluck('id')->all();

        $this->assertNotContains($sien->id, $trouves,
            'le périmètre ne survit pas aux branches `orWhere` : le groupe de OR '
            . 'doit être enfermé dans une fermeture, sinon le OR sort du périmètre');

        // Et le contrôle positif sur la MEME branche, pour que l'absence ci-dessus
        // ne soit pas celle d'une recherche qui ne rend jamais rien.
        $trouvesMien = app(ParcelInterface::class)
            ->parcelSearchs(new Request(['search' => '0022991234567']))
            ->pluck('id')->all();

        $this->assertContains($mien->id, $trouvesMien,
            'la recherche par téléphone ne rend plus rien du tout : l\'absence '
            . 'ci-dessus ne prouverait alors pas le périmètre');
    }

    /* ─────────── les soldes lus avant que le dépôt ne refuse ─────────── */

    /**
     * `processed()` lisait DEUX ressources d'en face : le versement (son montant)
     * et le compte (son solde), comparés avant tout refus du dépôt. S51 avait
     * corrigé `paymentStore()` ; ces méthodes-là n'étaient pas couvertes.
     */
    public function test_the_hub_payment_processing_never_reads_another_companys_balance(): void
    {
        // ⚠️ Sans ce montage, l'appel rend 404 et les assertions d'absence
        // ci-dessous passent sur du vide : le sabotage de la garde est resté
        // VERT au premier jet, exactement pour cette raison.
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $sonCompte = $this->compteDe(self::AUTRE, 10);
        $sonVersement = HubPayment::forceCreate([
            'company_id' => self::AUTRE, 'hub_id' => $this->hubDe(self::AUTRE)->id,
            'amount' => 1000, 'status' => 1, 'created_by' => UserType::ADMIN,
        ]);

        $agent = $this->agentDe((int) settings()->id, 'Agent', ['hub_payment_process']);

        $this->actingAs($agent)->put(self::HOTE . '/admin/hub-payment/processed', [
            'id' => $sonVersement->id, 'from_account' => $sonCompte->id,
            'amount' => 1000, 'transaction_id' => 'TR-S57',
        ])->assertRedirect();

        // Le message « solde insuffisant » révélerait l'état du compte d'en face.
        $this->assertStringNotContainsString(
            __('hub_payment.not_enough_courier_balance'),
            $this->messagesToastr(),
            'le contrôleur a comparé le montant au solde d\'une autre société',
        );
        // ⚠️ L'ANCRAGE : la preuve que le contrôleur a bien tourné et refusé.
        // Sans lui, un 404, un 403 ou une redirection d'abonnement rendrait les
        // deux assertions d'absence vraies sans rien avoir exercé.
        $this->assertStringContainsString(
            __('hub_payment.error_msg'),
            $this->messagesToastr(),
            'la requête n\'a pas atteint le contrôleur : ce cas ne prouverait rien',
        );
        $this->assertSame(1, (int) $sonVersement->fresh()->status,
            'le versement d\'une autre société a changé d\'état');
    }

    /**
     * `update()` du versement d'entrepôt : même lecture nue, méthode voisine.
     *
     * Deux points d'appel, deux preuves — la leçon de S47, et la raison pour
     * laquelle je ne livre pas la seconde garde sur la foi de la première.
     */
    public function test_the_hub_payment_update_never_reads_another_companys_balance(): void
    {
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $sonCompte = $this->compteDe(self::AUTRE, 10);
        $mien = HubPayment::forceCreate([
            'company_id' => (int) settings()->id, 'hub_id' => $this->hubDe((int) settings()->id)->id,
            'amount' => 500, 'status' => 1, 'created_by' => UserType::ADMIN,
        ]);

        $this->actingAs($this->agentDe((int) settings()->id, 'Agent', ['hub_payment_update']))
            ->put(self::HOTE . '/admin/request/hub/payment/update/' . $mien->id, [
                'hub_id' => $this->hubDe((int) settings()->id)->id,
                'from_account' => $sonCompte->id, 'isprocess' => 1,
                'amount' => 1000, 'transaction_id' => 'TR-S57', 'description' => 'S57',
            ])->assertRedirect();

        $this->assertStringContainsString(__('hub_payment.error_msg'), $this->messagesToastr(),
            'la requête n\'a pas atteint le contrôleur : ce cas ne prouverait rien');
        $this->assertStringNotContainsString(
            __('hub_payment.not_enough_courier_balance'), $this->messagesToastr(),
            'le contrôleur a comparé le montant au solde du compte d\'une autre société');
    }

    /** Et `update()` du versement marchand, dernière méthode de la famille. */
    public function test_the_merchant_payment_update_never_reads_another_companys_balance(): void
    {
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $sien = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $sien->current_balance = 10;
        $sien->save();

        $this->actingAs($this->agentDe((int) settings()->id, 'Agent', ['payment_update']))
            ->put(self::HOTE . '/admin/payment/update', [
                'id' => 1, 'merchant' => $sien->id, 'merchant_account' => 1,
                'from_account' => $this->compteDe((int) settings()->id, 500000)->id,
                'isprocess' => 1, 'amount' => 1000,
                'transaction_id' => 'TR-S57', 'description' => 'S57', 'date' => date('Y-m-d'),
            ]);

        $this->assertStringContainsString(__('merchantmanage.error_msg'), $this->messagesToastr(),
            'la requête n\'a pas atteint le contrôleur : ce cas ne prouverait rien');
        $this->assertStringNotContainsString(
            __('merchantmanage.not_enough_merchant_balance'), $this->messagesToastr(),
            'le contrôleur a comparé le montant au solde d\'un marchand d\'une autre société');
    }

    /* ────────────────────────────── fixtures ───────────────────────────────── */

    private function ajax(string $uri, array $donnees): string
    {
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $agent = $this->agentDe((int) settings()->id, 'Operateur', self::DROITS[$uri] ?? []);

        $reponse = $this->actingAs($agent)
            ->post(self::HOTE . $uri, $donnees, ['X-Requested-With' => 'XMLHttpRequest']);

        // ⚠️ L'ancrage : une assertion d'absence est vraie sur une page « Accès
        // interdit » ou une redirection d'abonnement. Leçon des lots S51 à S56.
        $reponse->assertOk();

        return $reponse->getContent();
    }

    private function messagesToastr(): string
    {
        return json_encode(session('toastr::messages') ?? [], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function agentDe(int $societe, string $nom, array $droits = []): User
    {
        $n = User::count();
        $u = new User();
        $u->company_id = $societe;
        $u->name = $nom;
        $u->email = 'u.s57.' . $societe . '.' . $n . '@example.test';
        $u->mobile = '0022996' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::ADMIN;
        $u->permissions = $droits;
        $u->save();

        return $u;
    }

    private function colisDe(int $societe, string $client, string $telephone): Parcel
    {
        $marchand = Merchant::where('company_id', $societe)->first() ?? $this->marchandDe($societe);

        return Parcel::forceCreate([
            'company_id' => $societe, 'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-S57-' . $societe . '-' . uniqid(),
            'customer_name' => $client, 'customer_phone' => $telephone,
            'customer_address' => 'Cotonou', 'cash_collection' => 10000,
            'current_payable' => 9000, 'status' => ParcelStatus::PENDING,
            'priority_type_id' => 1,
        ]);
    }

    private function marchandDe(int $societe): Merchant
    {
        $u = $this->agentDe($societe, 'Marchand S57');
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME S57 ' . $societe, 'current_balance' => 0,
            'opening_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function compteDe(int $societe, float $solde): Account
    {
        return Account::forceCreate([
            'company_id' => $societe, 'balance' => $solde,
            'account_holder_name' => 'Titulaire ' . $societe,
            'account_no' => 'CPT-S57-' . $societe,
        ]);
    }

    private function hubDe(int $societe): Hub
    {
        return Hub::firstWhere('name', 'Entrepot S57 ' . $societe) ?? Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot S57 ' . $societe,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);
    }
}
