<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Backend\HubInChargeController;
use App\Models\Backend\Hub;
use App\Models\Backend\HubInCharge;
use App\Models\User;
use App\Repositories\HubInCharge\HubInChargeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S31 — les responsables d'entrepôt, sixième passe sur l'arriéré du filet.
 *
 * `HubInChargeRepository` était scopé par `hub_id` **et par rien d'autre**. Or
 * `hub_incharges` ne porte pas de `company_id` : le périmètre doit passer par
 * l'entrepôt, exactement comme `merchant_shops` passe par le marchand (S26).
 * `where('hub_id', $hubID)` acceptait donc l'entrepôt de n'importe quelle société.
 *
 * Neuf points dans un seul dépôt, et le plus grave n'est pas une lecture :
 *
 * | Méthode | Ce qu'elle faisait |
 * |---|---|
 * | `all`, `get` | la liste et la fiche des responsables d'un entrepôt d'autrui |
 * | `hub` | `Hub::findOrFail($hubID)` nu — la fiche de l'entrepôt |
 * | `users` | `User::where('user_type', ADMIN)` **sans filtre** : le menu déroulant nommait les administrateurs de **tous** les transporteurs |
 * | `store`, `update` | nommer un responsable sur l'entrepôt d'autrui, avec n'importe quel agent |
 * | `assignedHub` | 🔴 sa rafle passe **tous** les autres responsables actifs de l'entrepôt à inactif — chez l'autre société, une **interruption de service** |
 * | `assignedHub` | et elle réécrivait le `hub_id` d'un utilisateur sans vérifier qu'il est à nous |
 * | `delete` | `HubInCharge::destroy($id)` nu, **sans même le `hub_id`** |
 *
 * ⚠️ Deux `HubInCharge::where(...)` du contrôleur restent non scopés à dessein :
 * ce sont des contrôles d'**unicité**. Non scopés ils rejettent *trop*, ils ne
 * divulguent rien — et depuis que `users()` est scopé, un agent ne peut plus y
 * soumettre l'identifiant d'un utilisateur d'autrui. Les laisser fait même surfacer
 * les données aberrantes qu'a pu laisser l'ancien défaut.
 */
class HubInChargeScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private Hub $monEntrepot;
    private Hub $sonEntrepot;
    private User $monAgent;
    private User $sonAgent;
    private HubInCharge $monResponsable;
    private HubInCharge $sonResponsable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monEntrepot = $this->entrepotDe(settings()->id);
        $this->sonEntrepot = $this->entrepotDe(self::AUTRE);
        $this->monAgent = $this->agentDe(settings()->id);
        $this->sonAgent = $this->agentDe(self::AUTRE);
        $this->monResponsable = $this->responsable($this->monEntrepot, $this->monAgent);
        $this->sonResponsable = $this->responsable($this->sonEntrepot, $this->sonAgent);

        $this->actingAs($this->monAgent);
    }

    /* ─────────────────────────── les lectures ───────────────────────────── */

    public function test_the_hub_of_another_company_is_out_of_reach(): void
    {
        $depot = app(HubInChargeInterface::class);

        $this->assertEmpty($depot->all($this->sonEntrepot->id), 'la liste des responsables d\'un entrepôt d\'autrui');
        $this->assertNull($depot->get($this->sonEntrepot->id, $this->sonResponsable->id));

        $this->expectException(ModelNotFoundException::class);
        $depot->hub($this->sonEntrepot->id);
    }

    /**
     * Le menu déroulant de l'écran : il nommait les administrateurs de **tous** les
     * transporteurs. Ce n'est pas un identifiant qui fuyait, c'est un annuaire.
     */
    public function test_the_user_dropdown_no_longer_lists_every_carriers_administrators(): void
    {
        $noms = app(HubInChargeInterface::class)->users()->pluck('company_id')->unique()->all();

        $this->assertSame([settings()->id], array_values($noms),
            'le menu déroulant liste des administrateurs d\'autres sociétés');
    }

    /* ─────────────────────────── les écritures ──────────────────────────── */

    public function test_no_one_can_be_appointed_on_another_companys_hub(): void
    {
        $avant = HubInCharge::count();

        $this->assertFalse((bool) app(HubInChargeInterface::class)->store($this->sonEntrepot->id, new Request([
            'user_id' => $this->monAgent->id, 'status' => Status::ACTIVE,
        ])));

        $this->assertSame($avant, HubInCharge::count(), 'un responsable a été nommé sur l\'entrepôt d\'autrui');
    }

    public function test_an_agent_of_another_company_cannot_be_appointed_on_my_hub(): void
    {
        $avant = HubInCharge::count();

        $this->assertFalse((bool) app(HubInChargeInterface::class)->store($this->monEntrepot->id, new Request([
            'user_id' => $this->sonAgent->id, 'status' => Status::ACTIVE,
        ])));

        $this->assertSame($avant, HubInCharge::count());
        $this->assertNull($this->sonAgent->fresh()->hub_id, 'le hub_id d\'un agent d\'autrui a été réécrit');
    }

    public function test_the_appointment_of_another_company_cannot_be_rewritten(): void
    {
        $agentDorigine = $this->sonResponsable->user_id;

        $this->assertFalse((bool) app(HubInChargeInterface::class)->update(
            $this->sonEntrepot->id, $this->sonResponsable->id,
            new Request(['user_id' => $this->monAgent->id, 'status' => Status::ACTIVE])
        ));

        $this->assertSame($agentDorigine, $this->sonResponsable->fresh()->user_id);
    }

    public function test_the_appointment_of_another_company_cannot_be_deleted(): void
    {
        $this->assertFalse((bool) app(HubInChargeInterface::class)->delete($this->sonResponsable->id));
        $this->assertNotNull(HubInCharge::find($this->sonResponsable->id));
    }

    /**
     * 🔴 Le défaut le plus grave du lot : la rafle d'`assignedHub()` passe **tous**
     * les autres responsables actifs de l'entrepôt à inactif. Sur l'entrepôt d'une
     * autre société, c'est une interruption de service — son responsable en place
     * perdait son affectation.
     */
    public function test_the_active_appointments_of_another_company_are_not_deactivated(): void
    {
        $this->assertSame(Status::ACTIVE, (int) $this->sonResponsable->fresh()->status, 'la fixture doit partir d\'un responsable actif');

        $intrus = $this->responsable($this->sonEntrepot, $this->monAgent, Status::INACTIVE);

        $this->assertFalse((bool) app(HubInChargeInterface::class)->assignedHub($this->sonEntrepot->id, $intrus));

        $this->assertSame(Status::ACTIVE, (int) $this->sonResponsable->fresh()->status,
            'le responsable en place d\'une autre société a été désactivé');
        $this->assertSame(Status::INACTIVE, (int) $intrus->fresh()->status);
    }


    /**
     * Le dernier garde d'`assignedHub()`, et il demande une mise en scène précise.
     *
     * La ligne réécrivait le `hub_id` d'un utilisateur sans vérifier qu'il est à nous.
     * Depuis les gardes précédents, ce cas n'est plus atteignable **à partir d'une
     * base saine** : `store()` et `update()` refusent un agent d'autrui. Mais il
     * reste atteignable sur les données que l'ancien défaut a pu laisser — une ligne
     * `hub_incharges` qui apparie NOTRE entrepôt à LEUR agent.
     *
     * On reproduit donc exactement cet état aberrant. Sans ce test, le garde ne
     * serait jamais exercé : son sabotage restait vert.
     */
    public function test_a_leftover_row_pairing_my_hub_with_their_agent_cannot_reassign_them(): void
    {
        // L'état que l'ancien défaut produisait : mon entrepôt, leur agent.
        $aberrante = $this->responsable($this->monEntrepot, $this->sonAgent, Status::INACTIVE);

        $this->assertNull($this->sonAgent->fresh()->hub_id, 'la fixture doit partir d\'un agent sans entrepôt');

        $this->assertFalse((bool) app(HubInChargeInterface::class)->assignedHub($this->monEntrepot->id, $aberrante));

        $this->assertNull($this->sonAgent->fresh()->hub_id,
            'l\'agent d\'une autre société a été rattaché à notre entrepôt');
    }

    /* ────────────────────────── les écrans ──────────────────────────────── */

    public function test_the_screens_answer_not_found_out_of_scope(): void
    {
        $controleur = app(HubInChargeController::class);

        $appels = [
            'index' => fn () => $controleur->index($this->sonEntrepot->id),
            'create' => fn () => $controleur->create($this->sonEntrepot->id),
            'edit' => fn () => $controleur->edit($this->sonEntrepot->id, $this->sonResponsable->id),
            'assigned' => fn () => $controleur->assigned($this->sonEntrepot->id, $this->sonResponsable->id),
            'destroy' => fn () => $controleur->destroy($this->sonEntrepot->id, $this->sonResponsable->id),
        ];

        $ouverts = [];
        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException|ModelNotFoundException $e) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame([], $ouverts, "Écrans ouverts sur l'entrepôt d'une autre société :\n - "
            . implode("\n - ", $ouverts));
    }

    /* ───────────────────── les contrôles négatifs ───────────────────────── */

    public function test_my_own_hub_still_works_end_to_end(): void
    {
        $depot = app(HubInChargeInterface::class);

        $this->assertNotEmpty($depot->all($this->monEntrepot->id));
        $this->assertNotNull($depot->get($this->monEntrepot->id, $this->monResponsable->id));
        $this->assertNotNull($depot->hub($this->monEntrepot->id));

        $autreAgent = $this->agentDe(settings()->id, 'second');

        $this->assertTrue((bool) $depot->store($this->monEntrepot->id, new Request([
            'user_id' => $autreAgent->id, 'status' => Status::ACTIVE,
        ])));

        // La nomination active bascule bien l'ancienne chez NOUS — c'est l'objet de
        // la rafle, et elle doit continuer de fonctionner dans son périmètre.
        $this->assertSame(Status::INACTIVE, (int) $this->monResponsable->fresh()->status);
        $this->assertSame($this->monEntrepot->id, $autreAgent->fresh()->hub_id);

        $this->assertTrue((bool) $depot->delete($this->monResponsable->id));
        $this->assertNull(HubInCharge::find($this->monResponsable->id));
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function entrepotDe(int $societe): Hub
    {
        return Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot ' . $societe,
            'phone' => '0022997000' . $societe, 'address' => 'Cotonou', 'status' => Status::ACTIVE,
        ]);
    }

    private function agentDe(int $societe, string $suffixe = 'premier'): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent ' . $suffixe . ' ' . $societe;
        $agent->email = 'agent.' . $suffixe . '.' . $societe . '@example.test';
        $agent->mobile = '0022997' . $societe . strlen($suffixe) . rand(1000, 9999);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function responsable(Hub $entrepot, User $agent, int $statut = Status::ACTIVE): HubInCharge
    {
        return HubInCharge::forceCreate([
            'user_id' => $agent->id, 'hub_id' => $entrepot->id, 'status' => $statut,
        ]);
    }
}
