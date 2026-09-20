<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\DeliverycategoryController;
use App\Http\Controllers\Backend\DeliveryManController;
use App\Http\Controllers\Backend\FraudController;
use App\Http\Controllers\Backend\UserController;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Fraud;
use App\Models\Backend\Hub;
use App\Models\Backend\PushNotification;
use App\Models\Backend\Role;
use App\Models\User;
use App\Repositories\DeliveryCategory\DeliveryCategoryInterface;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Fraud\FraudInterface;
use App\Repositories\PushNotification\PushNotificationInterface;
use App\Repositories\User\UserInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S35 — les comptes et le paramétrage. **Dixième et dernière passe** : l'arriéré
 * que `WebIsolationCoverageTest` avait gelé à 171 routes tombe à **zéro**.
 *
 * Les 19 routes qui restaient se répartissaient en cinq états, et il valait la
 * peine de les nommer plutôt que de les traiter en bloc :
 *
 * | État | Routes | Suite donnée |
 * |---|---|---|
 * | déjà correctes | 4 (`customs` ×3, `delivery-zone/countries`) | un test, rien d'autre |
 * | lecture nue | 2 (`fraud/edit`, `users/permissions`) | scopées |
 * | reprise de ligne | 3 écritures (`users`, `deliveryman`, `delivery-category`) | scopées |
 * | 500 au lieu de 404 | 6 suppressions | forme gardée réparée |
 * | ni l'un ni l'autre | 2 exemptées (`category`), 5 **mortes** (`sms-settings`) | motivées / retirées |
 *
 * 🔴 **Le pire du lot : les permissions.** `UserController::permission()` lisait
 * `User::where('id', $id)->first()` — ni société, ni type — et
 * `UserRepository::permissionUpdate()` **écrivait** le jeu de permissions sur la
 * même lecture nue, avec un identifiant venu du **corps** de la requête
 * (`PUT admin/users/permissions/update`). On réécrivait donc les droits de
 * n'importe quel compte de n'importe quelle société. C'est la **cinquième**
 * occurrence de la tache aveugle du filet, et la dernière du chantier.
 *
 * `UserRepository::update()` s'y ajoutait : `User::find($id)` nu suivi de
 * `company_id = settings()->id`, sur un écran qui réécrit l'e-mail, le **mot de
 * passe**, le rôle et les permissions — la reprise de compte de S34, cette fois
 * sur l'administrateur d'un autre transporteur.
 */
class UserAndSettingsScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private User $monAgent;
    private User $sonAgent;
    private Role $monRole;
    private Role $sonRole;
    private DeliveryMan $monLivreur;
    private DeliveryMan $sonLivreur;
    private Fraud $monSignalement;
    private Fraud $sonSignalement;
    private Deliverycategory $maCategorie;
    private Deliverycategory $saCategorie;
    private int $monEntrepot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->sonRole = Role::where('company_id', self::AUTRE)->firstOrFail();
        $this->sonAgent = $this->agentDe(self::AUTRE, 'voisin', $this->sonRole->id);

        $this->monRole = Role::forceCreate([
            'company_id' => settings()->id, 'name' => 'Agent', 'slug' => 'agent-s35',
            'permissions' => ['dashboard_read'], 'status' => 1,
        ]);
        $this->monAgent = $this->agentDe(settings()->id, 'moi', $this->monRole->id);

        $this->actingAs($this->agentDe(settings()->id, 'acteur', $this->monRole->id));

        $this->monEntrepot = Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Entrepot S35', 'status' => 1,
        ])->id;

        $this->monLivreur = $this->livreurDe(settings()->id);
        $this->sonLivreur = $this->livreurDe(self::AUTRE);

        $this->monSignalement = $this->signalementDe(settings()->id, '0022990000001');
        $this->sonSignalement = $this->signalementDe(self::AUTRE, '0022990000002');

        $this->maCategorie = Deliverycategory::forceCreate([
            'company_id' => settings()->id, 'title' => 'Ma categorie', 'status' => 1, 'position' => 1,
        ]);
        // ⚠️ `DeliverycategorySeeder` cree ses six categories **sans société** —
        // c'est ce que la clause `id == 1` de `get()` traduit : la categorie 1 est
        // le defaut partage. Aucune n'appartient donc a la societe 2, et il faut la
        // creer pour avoir un voisin a viser.
        $this->saCategorie = Deliverycategory::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Sa categorie', 'status' => 1, 'position' => 1,
        ]);
    }

    /* ═════════════ les permissions : le cœur du lot ════════════════════════ */

    /**
     * 🔴 `permissionUpdate()` écrivait sur une lecture sans société **ni type**,
     * avec un identifiant venu du corps de la requête.
     */
    public function test_the_permissions_of_another_companys_account_cannot_be_rewritten(): void
    {
        $avant = $this->sonAgent->permissions;

        $this->assertFalse((bool) app(UserInterface::class)
            ->permissionUpdate($this->sonAgent->id, new Request(['permissions' => ['parcel_read']])));

        $this->assertSame($avant, $this->sonAgent->fresh()->permissions,
            'les droits du compte d\'un autre transporteur ont été réécrits');
    }

    /**
     * Et le filtre sur `user_type` compte : un **marchand** ou un **livreur** de ma
     * propre société n'est pas joignable par cet écran. Le socle les atteignait,
     * et leur jeu de permissions n'a rien à voir avec celui d'un agent.
     */
    public function test_a_merchant_account_of_my_own_company_is_not_reachable_by_this_screen(): void
    {
        $marchand = User::where('company_id', settings()->id)
            ->where('user_type', UserType::MERCHANT)->first()
            ?? $this->compteDe(settings()->id, UserType::MERCHANT);

        $this->assertFalse((bool) app(UserInterface::class)
            ->permissionUpdate($marchand->id, new Request(['permissions' => ['parcel_read']])));
    }

    /** Contrôle négatif : sur mon agent, la mise à jour passe. */
    public function test_the_permissions_of_my_own_agent_can_still_be_rewritten(): void
    {
        $this->assertTrue((bool) app(UserInterface::class)
            ->permissionUpdate($this->monAgent->id, new Request(['permissions' => ['parcel_read']])));

        $this->assertSame(['parcel_read'], $this->monAgent->fresh()->permissions);
    }

    /* ═════════════ le compte lui-même ══════════════════════════════════════ */

    /** 🔴 Reprise de compte : e-mail, mot de passe, rôle et permissions. */
    public function test_the_account_of_another_company_cannot_be_rewritten(): void
    {
        $courrielDorigine = $this->sonAgent->email;
        $motDePasseDorigine = $this->sonAgent->password;

        $this->assertFalse((bool) app(UserInterface::class)
            ->update($this->sonAgent->id, $this->requeteDeCompte()));

        $apres = $this->sonAgent->fresh();

        $this->assertSame($courrielDorigine, $apres->email);
        $this->assertSame($motDePasseDorigine, $apres->password,
            'le mot de passe du compte d\'un autre transporteur a été réécrit');
        $this->assertSame(self::AUTRE, (int) $apres->company_id,
            'le compte a été rapatrié dans ma société');
    }

    /** Et le rôle désigné doit être le mien : c'est lui qui porte les droits. */
    public function test_my_account_cannot_be_given_another_companys_role(): void
    {
        $roleDorigine = $this->monAgent->role_id;

        $this->assertFalse((bool) app(UserInterface::class)
            ->update($this->monAgent->id, $this->requeteDeCompte($this->sonRole->id)));

        $this->assertSame($roleDorigine, $this->monAgent->fresh()->role_id,
            'mon agent a reçu le rôle — donc les droits — d\'une autre société');
    }

    /**
     * Même règle à la création, dont l'identifiant vient aussi du formulaire.
     *
     * ⚠️ Ce test a dû être écrit deux fois, et la raison mérite d'être gardée.
     * Sans `hub_id`, le socle traversait la branche `$role->permissions` : lire une
     * propriété sur `null` est un simple *warning* PHP, que Laravel promeut en
     * `ErrorException` — le `catch` l'avalait et rendait `false`. La création était
     * donc refusée **par accident**, et le sabotage de mon garde restait vert.
     *
     * Avec `hub_id`, cette branche est sautée : les permissions viennent de
     * `hubPermissions()`, `$role` n'est jamais déréférencé, rien ne lève — et le
     * compte se créait avec le `role_id` d'une autre société. C'est le chemin
     * réellement atteignable, et c'est celui qu'il faut exercer.
     */
    public function test_no_account_can_be_created_with_another_companys_role(): void
    {
        $depot = app(UserInterface::class);
        $avant = User::count();

        // Sans entrepôt : le socle refusait déjà, mais sans le savoir.
        $this->assertFalse((bool) $depot->store($this->requeteDeCompte($this->sonRole->id)));

        // Avec entrepôt : rien ne levait, et le compte naissait avec le rôle du voisin.
        $avecEntrepot = $this->requeteDeCompte($this->sonRole->id);
        $avecEntrepot->merge(['hub_id' => $this->monEntrepot, 'email' => 'avec.entrepot@example.test',
            'mobile' => '0022997555555']);

        $this->assertFalse((bool) $depot->store($avecEntrepot),
            'un compte a été créé avec le rôle — donc les droits — d\'une autre société');

        $this->assertSame($avant, User::count());
    }

    /** Contrôle négatif : avec MON rôle, la création passe, entrepôt ou non. */
    public function test_an_account_can_still_be_created_with_my_own_role(): void
    {
        $depot = app(UserInterface::class);
        $avant = User::count();

        $avecEntrepot = $this->requeteDeCompte();
        $avecEntrepot->merge(['hub_id' => $this->monEntrepot]);

        $this->assertTrue((bool) $depot->store($avecEntrepot));
        $this->assertSame($avant + 1, User::count());

        $cree = User::orderByDesc('id')->firstOrFail();
        $this->assertSame($this->monRole->id, (int) $cree->role_id);
    }

    public function test_the_account_of_another_company_cannot_be_deleted(): void
    {
        $this->assertFalse((bool) app(UserInterface::class)->delete($this->sonAgent->id));
        $this->assertNotNull(User::find($this->sonAgent->id));
    }

    /** Contrôle négatif : mon agent se modifie et se supprime bien. */
    public function test_my_own_agent_can_still_be_updated_and_deleted(): void
    {
        $depot = app(UserInterface::class);

        $this->assertTrue((bool) $depot->update($this->monAgent->id, $this->requeteDeCompte()));
        $this->assertSame('nouveau.agent@example.test', $this->monAgent->fresh()->email);

        $this->assertSame('delete', $depot->delete($this->monAgent->id));
        $this->assertNull(User::find($this->monAgent->id));
    }

    public function test_the_two_account_screens_answer_not_found_out_of_scope(): void
    {
        $controleur = app(UserController::class);
        $ouverts = [];

        $appels = [
            'users/edit' => fn () => $controleur->edit($this->sonAgent->id),
            'users/permissions' => fn () => $controleur->permission($this->sonAgent->id),
            'user/delete' => fn () => $controleur->destroy($this->sonAgent->id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $reponse = $appel();
                // `destroy` ne leve pas : il redirige avec un message d'erreur.
                if ($nom === 'user/delete') {
                    $this->assertNotNull(User::find($this->sonAgent->id), 'user/delete a supprimé hors périmètre');
                    continue;
                }
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans de compte ouverts hors périmètre :\n - "
            . implode("\n - ", $ouverts));
    }

    /** Et sur mon agent, les deux écrans rendent bien leur vue. */
    public function test_my_own_account_screens_still_open(): void
    {
        $controleur = app(UserController::class);

        $this->assertInstanceOf(\Illuminate\View\View::class, $controleur->edit($this->monAgent->id));
        $this->assertInstanceOf(\Illuminate\View\View::class, $controleur->permission($this->monAgent->id));
    }

    /* ═════════════ le paramétrage ══════════════════════════════════════════ */

    public function test_the_deliveryman_of_another_company_stays_out_of_reach(): void
    {
        $depot = app(DeliveryManInterface::class);
        $tarifDorigine = $this->sonLivreur->delivery_charge;

        $this->assertFalse((bool) $depot->update($this->sonLivreur->id, $this->requeteDeLivreur()));
        $this->assertFalse((bool) $depot->delete($this->sonLivreur->id));

        $apres = $this->sonLivreur->fresh();

        $this->assertNotNull($apres, 'le compte du livreur d\'une autre société a été supprimé');
        $this->assertSame(self::AUTRE, (int) $apres->company_id, 'sa fiche a été rapatriée');
        $this->assertSame($tarifDorigine, $apres->delivery_charge, 'son tarif de course a été réécrit');

        // Contrôle négatif : le mien se modifie et se supprime.
        $this->assertTrue((bool) $depot->update($this->monLivreur->id, $this->requeteDeLivreur()));
        $this->assertTrue((bool) $depot->delete($this->monLivreur->id));
    }

    public function test_the_fraud_report_of_another_company_is_neither_readable_nor_writable(): void
    {
        $depot = app(FraudInterface::class);
        $telephoneDorigine = $this->sonSignalement->phone;

        $this->assertNull($depot->get($this->sonSignalement->id),
            'le signalement de fraude d\'un autre transporteur était lisible');
        $this->assertFalse((bool) $depot->update($this->sonSignalement->id, $this->requeteDeSignalement()));
        $this->assertFalse((bool) $depot->delete($this->sonSignalement->id));

        $apres = $this->sonSignalement->fresh();
        $this->assertNotNull($apres);
        $this->assertSame($telephoneDorigine, $apres->phone);

        // Contrôle négatif : le mien.
        $this->assertNotNull($depot->get($this->monSignalement->id));
        $this->assertTrue((bool) $depot->update($this->monSignalement->id, $this->requeteDeSignalement()));
        $this->assertTrue((bool) $depot->delete($this->monSignalement->id));
    }

    /**
     * ⚠️ Volontairement **plus strict** que la lecture : `get()` laisse lire la
     * catégorie 1, partagée par toutes les sociétés. La laisser **réécrire**
     * laisserait n'importe quel transporteur renommer la catégorie de tous les
     * autres — et le socle y ajoutait `company_id = settings()->id`, donc se
     * l'appropriait.
     */
    public function test_the_delivery_category_of_another_company_cannot_be_taken_over(): void
    {
        $depot = app(DeliveryCategoryInterface::class);
        $titreDorigine = $this->saCategorie->title;

        $this->assertFalse((bool) $depot->update(
            new Request(['id' => $this->saCategorie->id, 'title' => 'Detournee', 'status' => 1, 'position' => 9])
        ));
        $this->assertFalse((bool) $depot->delete($this->saCategorie->id));

        $apres = $this->saCategorie->fresh();
        $this->assertSame(self::AUTRE, (int) $apres->company_id);
        $this->assertSame($titreDorigine, $apres->title);

        // Contrôle négatif : la mienne.
        $this->assertTrue((bool) $depot->update(
            new Request(['id' => $this->maCategorie->id, 'title' => 'Renommee', 'status' => 1, 'position' => 2])
        ));
        $this->assertTrue((bool) $depot->delete($this->maCategorie->id));
    }

    public function test_the_push_notification_of_another_company_cannot_be_deleted(): void
    {
        $depot = app(PushNotificationInterface::class);

        $sienne = PushNotification::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Chez le voisin', 'description' => 'x', 'type' => 'all',
        ]);
        $mienne = PushNotification::forceCreate([
            'company_id' => settings()->id, 'title' => 'Chez moi', 'description' => 'x', 'type' => 'all',
        ]);

        $this->assertFalse((bool) $depot->delete($sienne->id));
        $this->assertNotNull(PushNotification::find($sienne->id));

        // Contrôle négatif — et il vaut double : sans `upload`, le socle levait ici
        // une erreur avant même d'arriver au périmètre.
        $this->assertTrue((bool) $depot->delete($mienne->id));
    }

    public function test_the_three_parameterisation_screens_answer_not_found_out_of_scope(): void
    {
        $ouverts = [];

        $appels = [
            'deliveryman/edit' => fn () => app(DeliveryManController::class)->edit($this->sonLivreur->id),
            'deliveryman/delete' => fn () => app(DeliveryManController::class)->destroy($this->sonLivreur->id),
            'fraud/edit' => fn () => app(FraudController::class)->edit($this->sonSignalement->id),
            'fraud/delete' => fn () => app(FraudController::class)->destroy($this->sonSignalement->id),
            'delivery-category/delete' => fn () => app(DeliverycategoryController::class)->destroy($this->saCategorie->id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans de paramétrage ouverts hors périmètre :\n - "
            . implode("\n - ", $ouverts));
    }

    /* ═════════════ les cas qui ne sont pas des failles ═════════════════════ */

    /**
     * Les deux routes `category/*` sont **exemptées**, et ce test porte le motif :
     * la table `categorys` ne comporte **aucun** `company_id` — c'est un catalogue
     * de plateforme, comme `currencies` au constat S32 — et rien ne la consomme en
     * dehors de son propre CRUD.
     *
     * ⚠️ La réserve reste la même que pour `currencies` : le catalogue est
     * **partagé et modifiable**. Ce n'est pas un défaut de cloisonnement, c'est un
     * problème de catalogue commun, et il est signalé plutôt que corrigé.
     */
    public function test_the_shared_category_catalogue_carries_no_company_at_all(): void
    {
        $this->assertFalse(Schema::hasColumn('categorys', 'company_id'),
            'si cette table porte désormais une société, ces deux routes doivent être prouvées');
    }

    /**
     * Les cinq routes mortes de `sms-settings` sont **retirées**. Elles
     * désignaient des méthodes qui n'existent pas sur `SmsSettingsController` :
     * les atteindre rendait 500, et aucune vue ne les nommait.
     *
     * Ce qui reste — `update/{id}` — est **exempté** : son `{id}` est le nom de la
     * passerelle (`reve`, `twilio`, `nexmo`), pas l'identifiant d'une ressource, et
     * l'écriture est `companywise()` clé par clé.
     */
    public function test_the_sms_settings_controller_only_has_the_two_methods_it_declares(): void
    {
        $existantes = array_values(array_filter(
            ['index', 'create', 'store', 'edit', 'update', 'delete', 'status'],
            fn ($m) => method_exists(\App\Http\Controllers\Backend\SmsSettingsController::class, $m),
        ));

        $this->assertSame(['index', 'update'], $existantes,
            'toute route sms-settings au-delà de ces deux méthodes est morte');

        $nommees = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn ($nom) => $nom && str_starts_with($nom, 'sms-settings.'))
            ->values()->all();

        $this->assertSame(['sms-settings.index', 'sms-settings.update'], $nommees,
            'une route sms-settings morte a été remise');
    }

    /* ═════════════════════════════ fixtures ════════════════════════════════ */

    private function compteDe(int $societe, int $type, string $suffixe = 'x'): User
    {
        $compte = new User();
        $compte->company_id = $societe;
        $compte->name = 'Compte ' . $suffixe;
        $compte->email = 'compte.' . $suffixe . '.' . $type . '.' . $societe . '@example.test';
        $compte->mobile = '00229970' . $type . $societe . strlen($suffixe) . rand(10, 99);
        $compte->password = bcrypt('secret');
        $compte->user_type = $type;
        $compte->permissions = ['dashboard_read'];
        $compte->save();

        return $compte;
    }

    private function agentDe(int $societe, string $suffixe, int $roleId): User
    {
        $agent = $this->compteDe($societe, UserType::ADMIN, $suffixe);
        $agent->role_id = $roleId;
        $agent->save();

        return $agent->fresh();
    }

    private function livreurDe(int $societe): DeliveryMan
    {
        return DeliveryMan::forceCreate([
            'company_id' => $societe,
            'user_id' => $this->compteDe($societe, UserType::DELIVERYMAN, 'livreur')->id,
            'status' => 1,
            'delivery_charge' => 100 * $societe,
            'pickup_charge' => 50,
            'return_charge' => 25,
            'current_balance' => 0,
            'opening_balance' => 0,
        ]);
    }

    private function signalementDe(int $societe, string $telephone): Fraud
    {
        return Fraud::forceCreate([
            'company_id' => $societe,
            'created_by' => User::where('company_id', $societe)->firstOrFail()->id,
            'phone' => $telephone,
            'name' => 'Client signale ' . $societe,
            'details' => 'Motif',
            'tracking_id' => 'BL-' . $societe,
        ]);
    }

    private function requeteDeCompte(?int $roleId = null): Request
    {
        return new Request([
            'name' => 'Agent detourne',
            'email' => 'nouveau.agent@example.test',
            'password' => 'motdepasse-choisi',
            'mobile' => '0022997777777',
            'role_id' => $roleId ?? $this->monRole->id,
            'nid_number' => '123',
            'designation_id' => \App\Models\Backend\Designation::first()?->id,
            'department_id' => \App\Models\Backend\Department::first()?->id,
            'joining_date' => '2026-01-01',
            'address' => 'Cotonou',
            'salary' => 0,
            'status' => 1,
        ]);
    }

    private function requeteDeLivreur(): Request
    {
        return new Request([
            'lat' => '6.36', 'long' => '2.42',
            'delivery_charge' => 9999, 'pickup_charge' => 9999, 'return_charge' => 9999,
            // ⚠️ Absents, ces deux-la ecrivaient `null` sur des colonnes NOT NULL et
            // la mise a jour echouait d'elle-meme : le refus n'aurait plus rien
            // prouve. Meme lecon qu'en 5e passe (`Account::update`).
            'opening_balance' => 0, 'salary' => 0,
            'name' => 'Livreur detourne', 'email' => 'livreur.detourne@example.test',
            'mobile' => '0022997666666', 'address' => 'Ailleurs', 'status' => 1,
            'hub_id' => $this->monEntrepot,
        ]);
    }

    private function requeteDeSignalement(): Request
    {
        return new Request([
            'phone' => '0022990000999',
            'name' => 'Nom detourne',
            'details' => 'Motif detourne',
            'tracking_id' => 'BL-DETOURNE',
        ]);
    }
}
