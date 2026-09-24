<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S55 — le catalogue des devises passe au panneau du SUPER-ADMINISTRATEUR.
 *
 * Décision du 24/09, seconde étape : S54 avait **exempté** `currency/update` du
 * filet S38 au motif que le catalogue est celui de la plateforme ; ce lot ferme
 * la question que l'exemption laissait ouverte — celle de l'**accès**.
 *
 * ⚠️ **Ce test existe parce que j'avais surestimé le défaut.** J'ai écrit, dans
 * la PR #122, dans la cartographie et dans `CLAUDE.md`, qu'« une société qui
 * renomme une devise la renomme pour tout le monde ». **C'était faux comme
 * affirmé** : la mesure donne 57 entrées dans la table `permissions` du
 * locataire, et `currency_update` n'en fait pas partie — il n'existe que dans
 * `SuperAdminPermission`. Un administrateur de locataire portant **tous** ses
 * droits recevait déjà un refus.
 *
 * Ce qui était vrai, et qui reste le motif du lot :
 *
 * > La garantie était **de la donnée**, pas de la **structure**. Les six routes
 * > vivaient sous `admin/` avec `panel:back-office`, donc dans le panneau du
 * > locataire ; seul le contenu des semences les en tenait écartées. Ajouter
 * > `currency_*` à la table `permissions` — une ligne de semence, une écriture
 * > manuelle — aurait ouvert la surface sans que rien ne s'en aperçoive.
 *
 * Sous `super-admin/`, `panel:super-admin` n'admet que `UserType::SUPER_ADMIN`.
 * Le refus ne dépend plus de ce qu'on accorde : il dépend de **qui on est**.
 * C'est l'invariant de S41, et `WebPanelSeparationTest` l'exige pour tout ce
 * qui vit sous ce préfixe.
 */
class CurrencyPanelScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        // La devise que les cas ci-dessous tentent de renommer. `currencies` ne
        // porte aucune `company_id` : c'est precisement ce qui en fait un
        // catalogue de plateforme (constat S32).
        Currency::forceCreate([
            'id' => 1, 'country' => 'Benin', 'name' => 'XOF', 'symbol' => 'FCFA',
            'code' => 'XOF', 'exchange_rate' => 1, 'position' => 'right', 'status' => 1,
        ]);
    }

    /**
     * ⚠️ Le cœur du lot : l'administrateur du locataire porte **tous** les droits
     * que sa table `permissions` peut offrir — et il est refusé quand même.
     *
     * L'assertion ne porte donc pas sur « il n'a pas le droit » (ce serait la
     * garantie d'avant, celle de la donnée) mais sur « ce panneau n'est pas le
     * sien » : même en lui accordant de force `currency_update`, il reste dehors.
     */
    public function test_a_tenant_admin_is_refused_by_the_panel_even_holding_every_right(): void
    {
        $tousLesDroits = DB::table('permissions')->pluck('attribute')->all();

        // Le constat qui corrige mon propre énoncé : le droit n'existe pas côté locataire.
        $this->assertNotContains('currency_update', $tousLesDroits,
            'si `currency_update` entre dans la table `permissions` du locataire, '
            . 'ce lot doit être relu : la garde du panneau devient le seul rempart');

        // Et on le lui accorde QUAND MEME, pour que le refus ne puisse pas venir de là.
        $agent = $this->agent(UserType::ADMIN, array_merge($tousLesDroits, ['currency_update']));

        $this->actingAs($agent)
            ->put(self::HOTE . '/super-admin/currency/update', $this->champs())
            ->assertForbidden();

        $this->assertSame('XOF', Currency::find(1)?->name,
            'la devise a été renommée par un locataire');
    }

    /** Le marchand n'y a pas davantage sa place. */
    public function test_a_merchant_is_refused_by_the_panel(): void
    {
        $this->actingAs($this->agent(UserType::MERCHANT, ['currency_update']))
            ->put(self::HOTE . '/super-admin/currency/update', $this->champs())
            ->assertForbidden();
    }

    /**
     * Le contrôle positif, sans lequel les deux refus ci-dessus ne prouveraient
     * rien : le super-administrateur, lui, aboutit.
     */
    public function test_the_super_admin_still_reaches_the_platform_catalogue(): void
    {
        // ⚠️ `assertRedirect()` SEUL serait creux : un echec de VALIDATION redirige
        // aussi. C'est ce qui s'est produit au premier jet — `position` doit etre
        // numerique et j'envoyais « left », donc le controle positif passait sans
        // que rien ne soit ecrit. On exige donc la destination ET l'absence
        // d'erreurs de session.
        $this->actingAs($this->agent(UserType::SUPER_ADMIN, ['currency_update']))
            ->put(self::HOTE . '/super-admin/currency/update', $this->champs())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('currency.index'));

        $this->assertSame('Franc CFA', Currency::find(1)?->name,
            'le super-administrateur ne peut plus tenir le catalogue de la plateforme');
    }

    /** ⚠️ Et l'ancienne adresse ne répond plus : le doublon sous `admin/` a disparu. */
    public function test_the_old_tenant_uri_no_longer_exists(): void
    {
        $this->actingAs($this->agent(UserType::ADMIN, DB::table('permissions')->pluck('attribute')->all()))
            ->put(self::HOTE . '/admin/currency/update', $this->champs())
            ->assertNotFound();
    }

    /* ────────────────────────────── fixtures ───────────────────────────────── */

    private function champs(): array
    {
        return [
            'id' => 1, 'name' => 'Franc CFA', 'symbol' => 'FCFA',
            'exchange_rate' => 1, 'position' => 1, 'status' => 1,
        ];
    }

    private function agent(int $type, array $droits): User
    {
        $n = User::count();
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent S55';
        $u->email = 'agent.s55.' . $type . '.' . $n . '@example.test';
        $u->mobile = '0022997' . $type . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $u->password = bcrypt('secret');
        $u->user_type = $type;
        $u->permissions = $droits;
        $u->save();

        return $u;
    }
}
