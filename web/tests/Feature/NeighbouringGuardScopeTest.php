<?php

namespace Tests\Feature;

use App\Models\Backend\DeliveryZone;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;
use App\Repositories\Reports\ReportsInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S59 — la garde VOISINE : quand une garde dans la même méthode en absout une
 * autre qu'elle ne protège pas.
 *
 * L'instrument de S57 sautait **toute méthode contenant une garde**. Sa
 * granularité était donc la méthode — alors que la famille de défauts que
 * cette série poursuit depuis S45 est précisément « la ressource est gardée,
 * le second identifiant ne l'est pas », **dans la même méthode**. L'instrument
 * était aveugle à sa propre cible par construction.
 *
 * Deux resserrements (mesurés, voir `docs/outils/instrument-lectures-nues.py`) :
 *
 * | critère | ce qu'il exige |
 * |---|---|
 * | **position** | la garde doit PRÉCÉDER la lecture ; une garde en aval n'a rien empêché |
 * | **identifiant** | elle doit porter sur le MÊME champ ; une garde sur A ne protège pas B |
 *
 * 35 occurrences → 39, **sans en perdre une seule**. Les quatre nouvelles sont
 * réelles, et ce fichier les tient.
 */
class NeighbouringGuardScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    private int $maCategorie;
    private int $maZone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $this->rattacherLabonnementAuxReglages();
        $this->tariferMaSociete();
    }

    /* ─────── 1. l'oracle sur le portefeuille d'un marchand d'en face ─────── */

    /**
     * `POST admin/parcel/store` lisait `Merchant::find($request->merchant_id)`
     * **nu**, puis lisait `wallet_use_activation` et comparait à
     * `wallet_balance`. La lecture PRÉCÈDE le dépôt : la garde de S46 refusait
     * bien l'écriture, mais **après** que la réponse eut déjà renseigné.
     *
     * ⚠️ Le contrôle positif est la moitié qui compte. Il exige que le message
     * « low balance » soit REÇU pour notre propre marchand dans exactement la
     * même situation. Sans lui, l'absence du message côté étranger pourrait
     * venir de n'importe quoi — une validation qui refuse, un abonnement
     * expiré, une route absente — et ne prouverait rien (leçon S53).
     */
    public function test_the_wallet_of_a_foreign_merchant_never_answers(): void
    {
        $sien = $this->marchandDe(self::AUTRE, 'sien');
        $sien->wallet_use_activation = Status::ACTIVE;
        $sien->wallet_balance = 0;
        $sien->save();

        $messages = $this->poserUnColisPour($sien->id);

        $this->assertStringNotContainsString('low balance', $messages,
            'la reponse renseigne sur le PORTEFEUILLE d\'un marchand d\'une autre societe : '
            . '`Merchant::find($request->merchant_id)` a reperdu son `companywise()`');

        $this->assertStringContainsString(__('parcel.error_msg'), $messages,
            'le marchand etranger doit etre refuse par un message de refus, pas ignore en silence');

        $this->assertSame(0, Parcel::count(), 'aucun colis ne doit avoir ete cree');
    }

    /** Le contrôle positif : chez nous, dans la même situation, le message TOMBE. */
    public function test_our_own_merchant_does_answer_about_its_wallet(): void
    {
        $mien = $this->marchandDe(settings()->id, 'mien');
        $mien->wallet_use_activation = Status::ACTIVE;
        $mien->wallet_balance = 0;
        $mien->save();

        $this->assertStringContainsString('low balance', $this->poserUnColisPour($mien->id),
            'le chemin du portefeuille n\'est plus atteignable : le test d\'a cote ne prouve donc plus rien');
    }

    /**
     * Et l'autre moitié du défaut, de la famille **S15** : hors périmètre,
     * `find()` rend `null` et `$merchant->wallet_use_activation` déréférençait.
     * Un identifiant inexistant rendait **500** au lieu d'un refus.
     */
    public function test_an_unknown_merchant_is_refused_not_a_server_error(): void
    {
        $this->assertStringContainsString(__('parcel.error_msg'), $this->poserUnColisPour(999999),
            'un identifiant de marchand inconnu doit etre refuse proprement');
    }

    /* ──────── 2. les trois lectures voisines du rapport MHD ─────────────── */

    /**
     * `ReportsRepository::MHDreports()` lisait `Hub::find($request->hub_id)`
     * **nu**, et la ligne juste en dessous écrit
     * `HubStatement::companywise()->where('hub_id', $request->hub_id)` — sur le
     * MÊME identifiant. C'est cette voisine qui absolvait l'instrument.
     *
     * ⚠️ Ce n'est **pas** une divulgation constatée : `$MHDreports['hub']` n'est
     * rendu par aucune vue vivante. C'est un piège armé, et on ne laisse pas un
     * piège armé au motif qu'il n'a pas encore servi.
     */
    public function test_the_hub_of_another_company_is_not_resolved_by_the_report(): void
    {
        $sien = $this->entrepotDe(self::AUTRE);
        $mien = $this->entrepotDe(settings()->id);

        $this->assertNotNull($this->rapport(2, ['hub_id' => $mien->id])['hub'] ?? null,
            'le controle positif est tombe : le rapport ne resout plus NOTRE entrepot');

        $this->assertNull($this->rapport(2, ['hub_id' => $sien->id])['hub'] ?? null,
            'le rapport resout l\'entrepot d\'une AUTRE societe');
    }

    /** Même chose sur le livreur, seconde des trois lectures. */
    public function test_the_deliveryman_of_another_company_is_not_resolved_by_the_report(): void
    {
        $sien = $this->livreurDe(self::AUTRE, 'sien');
        $mien = $this->livreurDe(settings()->id, 'mien');
        $dates = ['parcel_date' => now()->subDay()->toDateString() . 'To' . now()->toDateString()];

        $this->assertNotNull($this->rapport(3, $dates + ['delivery_man_id' => $mien->id])['deliveryman'] ?? null,
            'le controle positif est tombe : le rapport ne resout plus NOTRE livreur');

        $this->assertNull($this->rapport(3, $dates + ['delivery_man_id' => $sien->id])['deliveryman'] ?? null,
            'le rapport resout le livreur d\'une AUTRE societe');
    }

    /**
     * La troisième, dans `MHDprint()`.
     *
     * ⚠️ Ce chemin est **MORT** : `MHDprint()` n'est appelée que par
     * `ReportsController::MHDPrintPage()`, qui n'est déclarée dans aucun fichier
     * de routes. Il est corrigé et tenu quand même — une méthode morte se
     * réveille en une ligne de route, et elle se réveillerait avec sa faille.
     */
    public function test_the_dead_print_path_is_guarded_too(): void
    {
        $sien = $this->livreurDe(self::AUTRE, 'sien');
        $mien = $this->livreurDe(settings()->id, 'mien');

        $impression = fn (int $id) => app(ReportsInterface::class)->MHDprint(
            new Request(['user_type' => 3, 'delivery_man_id' => $id, 'deliveryman_statement_ids' => '']),
        );

        $this->assertNotNull($impression($mien->id)['deliveryman'] ?? null,
            'le controle positif est tombe sur le chemin d\'impression');

        $this->assertNull($impression($sien->id)['deliveryman'] ?? null,
            'le chemin d\'impression resout le livreur d\'une AUTRE societe');
    }

    /* ───── 3. l'arriere du filet S58 : trois ecrans de filtre, PROUVES ───── */

    /**
     * S58 avait **lu** ces trois écrans et les avait jugés bornés : ils écrivent
     * `where('company_id', settings()->id)` à la main, sans passer par le scope.
     * Ils sont pourtant restés à l'arriéré, parce que **lu n'est pas prouvé**.
     *
     * Ce test les prouve, et il porte son contrôle positif **dans le même
     * appel** : le terme de recherche correspond aux DEUX enregistrements, le
     * nôtre et celui d'en face. Si l'écran cessait de rendre quoi que ce soit,
     * la présence tomberait avant que l'absence ne puisse passer pour une preuve.
     *
     * @dataProvider ecransDeFiltre
     */
    public function test_a_filter_screen_never_reaches_another_company(string $uri, string $droit, string $champ, string $semeur): void
    {
        $this->{$semeur}(settings()->id, 'MIEN');
        $this->{$semeur}(self::AUTRE, 'SIEN');

        $page = $this->filtrer($uri, $droit, [$champ => 'S59FILTRE']);

        $this->assertStringContainsString('S59FILTRE-MIEN', $page,
            "le controle positif est tombe sur {$uri} : l'ecran ne rend plus NOTRE ligne, "
            . "donc son silence sur celle d'en face ne prouve rien");

        $this->assertStringNotContainsString('S59FILTRE-SIEN', $page,
            "{$uri} rend une ligne d'une AUTRE societe");
    }

    public static function ecransDeFiltre(): array
    {
        return [
            'comptes'     => ['/admin/users/filter', 'user_read', 'name', 'agentNommeDe'],
            'entrepots'   => ['/admin/hubs/filter', 'hub_read', 'name', 'entrepotNommeDe'],
            'livreurs'    => ['/admin/deliveryman/filter', 'delivery_man_read', 'name', 'livreurNommeDe'],
        ];
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    /** Les messages Toastr déposés en session par la requête. */
    private function poserUnColisPour(int $marchandId): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['parcel_create'];
        $agent->save();

        $reponse = $this->actingAs($agent)->post(self::HOTE . '/admin/parcel/store', [
            'merchant_id' => $marchandId,
            'category_id' => $this->maCategorie,
            'zone_id' => $this->maZone,
            'delivery_type_id' => 1,
            'weight' => 1,
            'cash_collection' => 10000,
            'customer_name' => 'Client S59',
            'customer_phone' => '0022990000000',
            'customer_address' => 'Cotonou',
        ]);

        // L'ancre : sans elle, un 403 de permission ou un 302 d'abonnement
        // rendrait l'assertion d'absence vraie pour rien (leçon S51).
        $reponse->assertRedirect();
        $reponse->assertSessionHasNoErrors();

        return json_encode(session('toastr::messages') ?? [], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function rapport(int $typeUtilisateur, array $champs): array
    {
        return app(ReportsInterface::class)->MHDreports(
            new Request($champs + ['user_type' => $typeUtilisateur]),
        );
    }

    /**
     * ⚠️ `souscrireLeLocataire()` ne suffit PAS ici, et le test l'a dit avant moi.
     *
     * Ce harnais partagé satisfait `subscriptionCheckMiddleware`, qui passe par
     * `Auth::user()->subscription` — l'accesseur qui lit le DERNIER abonnement
     * de la société. Mais `ParcelController::store()` teste
     * `settings()->subscription`, qui est un tout autre chemin : un `belongsTo`
     * sur la colonne `general_settings.subscription_id`, que rien ne remplit.
     *
     * Sans cette ligne, les trois cas HTTP repartaient sur « Something went
     * wrong! » à la PREMIÈRE ligne de la méthode, sans jamais atteindre celle
     * qu'ils prétendent mesurer. C'est l'ancrage sur un message PRÉCIS qui l'a
     * révélé : une assertion sur « une redirection » serait passée au vert.
     *
     * Corrigé ici et non dans le trait partagé : une quarantaine de tests s'en
     * servent, et aucun n'a besoin de cette colonne.
     */
    private function rattacherLabonnementAuxReglages(): void
    {
        \Illuminate\Support\Facades\DB::table('general_settings')
            ->where('id', settings()->id)
            ->update([
                'subscription_id' => \Illuminate\Support\Facades\DB::table('subscriptions')
                    ->where('company_id', settings()->id)->orderByDesc('id')->value('id'),
            ]);
    }

    private function tariferMaSociete(): void
    {
        $zones = app(\App\Services\Pricing\ZoneCatalog::class)->installer(settings()->id);
        $this->maZone = $zones[DeliveryZone::COTONOU]->id;

        $this->maCategorie = Deliverycategory::forceCreate([
            'company_id' => settings()->id, 'title' => 'Standard S59',
            'status' => Status::ACTIVE, 'position' => 1,
        ])->id;

        $tarif = new DeliveryCharge();
        $tarif->company_id = settings()->id;
        $tarif->category_id = $this->maCategorie;
        $tarif->zone_id = $this->maZone;
        $tarif->weight = 1;
        $tarif->amount = 1500;
        $tarif->position = 1;
        $tarif->status = Status::ACTIVE;
        $tarif->save();
    }

    private function filtrer(string $uri, string $droit, array $parametres): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = [$droit];
        $agent->save();

        $reponse = $this->actingAs($agent)->get(self::HOTE . $uri . '?' . http_build_query($parametres));
        $reponse->assertOk();

        return $reponse->getContent();
    }

    private function agentNommeDe(int $societe, string $marque): User
    {
        $u = $this->agentDe($societe);
        $u->name = 'S59FILTRE-' . $marque;
        $u->save();

        return $u;
    }

    private function entrepotNommeDe(int $societe, string $marque): Hub
    {
        return Hub::forceCreate([
            'company_id' => $societe, 'name' => 'S59FILTRE-' . $marque,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);
    }

    private function livreurNommeDe(int $societe, string $marque): DeliveryMan
    {
        $livreur = $this->livreurDe($societe, $marque);
        $livreur->user->name = 'S59FILTRE-' . $marque;
        $livreur->user->save();

        return $livreur;
    }

    private function entrepotDe(int $societe): Hub
    {
        return Hub::firstWhere('name', 'Entrepot S59 ' . $societe) ?? Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot S59 ' . $societe,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);
    }

    private function livreurDe(int $societe, string $marque): DeliveryMan
    {
        $u = new User();
        $u->company_id = $societe;
        $u->name = 'Livreur S59 ' . $marque;
        $u->email = 'livreur.s59.' . $marque . '.' . $societe . '@example.test';
        $u->mobile = '00229971' . $societe . ord($marque[0]);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::DELIVERYMAN;
        // ⚠️ `backend/deliveryman/index.blade.php` rend `$deliveryman->user->hub->name` :
        // sans entrepot sur l'UTILISATEUR, l'ecran de filtre leve au lieu de rendre,
        // et le controle positif tomberait pour une raison qui n'est pas la portee.
        $u->hub_id = $this->entrepotDe($societe)->id;
        $u->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'status' => Status::ACTIVE, 'delivery_charge' => 500,
        ]);
    }

    private function marchandDe(int $societe, string $marque): Merchant
    {
        $u = new User();
        $u->company_id = $societe;
        $u->name = 'Marchand S59 ' . $marque;
        $u->email = 'm.s59.' . $marque . '.' . $societe . '@example.test';
        $u->mobile = '00229976' . $societe . ord($marque[0]);
        $u->password = bcrypt('secret');
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME S59 ' . $marque, 'current_balance' => 0,
            'opening_balance' => 0, 'wallet_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S59';
        $agent->email = 'agent.s59.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
