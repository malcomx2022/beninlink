<?php

namespace Tests\Feature;

use App\Models\Backend\Asset;
use App\Models\Backend\Assetcategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Hub;
use App\Models\Backend\HubPayment;
use App\Models\Backend\NewsOffer;
use App\Models\Backend\Packaging;
use App\Models\Backend\Role;
use App\Models\Backend\To_do;
use App\Models\User;
use App\Repositories\Asset\AssetInterface;
use App\Repositories\AssetCategory\AssetCategoryInterface;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use App\Repositories\Department\DepartmentInterface;
use App\Repositories\Designation\DesignationInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\HubManage\HubPayment\HubPaymentInterface;
use App\Repositories\NewsOffer\NewsOfferInterface;
use App\Repositories\Packaging\PackagingInterface;
use App\Repositories\Role\RoleInterface;
use App\Repositories\Todo\TodoInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S29, troisième passe — **le vol de ligne**, la forme la plus grave de
 * l'arriéré.
 *
 * Onze dépôts du back-office faisaient, dans leur `update()` :
 *
 *     $ligne = Modele::find($id);              // aucun périmètre
 *     $ligne->company_id = settings()->id;     // ← et là, la ligne change de main
 *     $ligne->save();
 *
 * La conséquence dépasse la lecture : la ligne d'une autre société n'était pas
 * seulement modifiable, elle était **transférée**. Elle disparaissait des écrans
 * de son propriétaire — sa liste est `companywise()` — et apparaissait dans les
 * nôtres. Un rôle et ses permissions, un entrepôt, un barème de livraison, un
 * versement à un hub : en changeant un identifiant dans un formulaire.
 *
 * Aucune trace ne l'aurait expliqué côté victime : la ligne n'est pas supprimée,
 * elle s'évapore.
 *
 * Le correctif est uniforme — les onze modèles portent déjà `scopeCompanywise` —
 * et refuse au lieu d'écrire : `Modele::companywise()->find(...)` puis un
 * `blank()` qui rend `false`, ce que les contrôleurs savent déjà afficher.
 */
class BackOfficeRecordTakeoverTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->actingAs($this->agent());
    }

    /**
     * Chaque dépôt, avec la ligne du voisin : l'écriture doit refuser, et la
     * ligne doit rester chez lui.
     */
    public function test_no_repository_takes_over_another_companys_record(): void
    {
        $voles = [];
        $refusManquants = [];

        foreach ($this->cas() as $nom => [$modele, $ligne, $ecriture]) {
            if ($ecriture() !== false) {
                $refusManquants[] = $nom;
            }

            if ((int) $modele::withoutGlobalScopes()->find($ligne->id)?->company_id !== self::AUTRE) {
                $voles[] = $nom;
            }
        }

        $this->assertSame([], $voles, "Lignes d'une autre société transférées à la nôtre :\n - "
            . implode("\n - ", $voles));

        $this->assertSame([], $refusManquants, "Écritures hors périmètre qui n'ont pas répondu `false` "
            . "— le contrôleur annoncerait un succès :\n - " . implode("\n - ", $refusManquants));
    }

    /** Et rien du contenu du voisin n'a bougé au passage. */
    public function test_nothing_of_the_neighbours_record_was_modified(): void
    {
        $modifies = [];

        foreach ($this->cas() as $nom => [$modele, $ligne, $ecriture]) {
            $avant = $ligne->getAttributes();
            $ecriture();
            $apres = $modele::withoutGlobalScopes()->find($ligne->id)?->getAttributes() ?? [];

            foreach (['name', 'title', 'amount', 'price'] as $colonne) {
                if (array_key_exists($colonne, $avant) && ($apres[$colonne] ?? null) !== $avant[$colonne]) {
                    $modifies[] = $nom . '.' . $colonne;
                }
            }
        }

        $this->assertSame([], $modifies, "Contenu d'une autre société réécrit :\n - " . implode("\n - ", $modifies));
    }

    /** Les lectures que cette passe a scopées, elles aussi. */
    public function test_the_reads_this_pass_scoped_return_nothing_out_of_scope(): void
    {
        $ouvertes = [];

        $lectures = [
            'role' => [RoleInterface::class, $this->role(self::AUTRE)->id],
            'asset' => [AssetInterface::class, $this->asset(self::AUTRE)->id],
            'assetcategory' => [AssetCategoryInterface::class, $this->assetCategory(self::AUTRE)->id],
            'hub' => [HubInterface::class, $this->hub(self::AUTRE)->id],
            'todo' => [TodoInterface::class, $this->todo(self::AUTRE)->id],
            'hub_payment' => [HubPaymentInterface::class, $this->hubPayment(self::AUTRE)->id],
            // Celles-ci etaient DEJA scopees avant cette passe (`where(['company_id'=>…])`
            // ou `companywise()`). On les inscrit pour que leurs routes le soient aussi.
            'designation' => [DesignationInterface::class, $this->designation(self::AUTRE)->id],
            'packaging' => [PackagingInterface::class, $this->packaging(self::AUTRE)->id],
            'department' => [DepartmentInterface::class, $this->department(self::AUTRE)->id],
            'delivery_charge' => [DeliveryChargeInterface::class, $this->deliveryCharge(self::AUTRE)->id],
            'news_offer' => [NewsOfferInterface::class, $this->newsOffer(self::AUTRE)->id],
        ];

        foreach ($lectures as $nom => [$interface, $id]) {
            if (filled(app($interface)->get($id))) {
                $ouvertes[] = $nom;
            }
        }

        $this->assertSame([], $ouvertes, "Écrans de modification ouverts sur la ligne d'une autre société :\n - "
            . implode("\n - ", $ouvertes));
    }

    /**
     * Le contrôle négatif : les mêmes dépôts lisent et écrivent bien MES lignes.
     * Sans lui, un périmètre qui ne renverrait jamais rien ferait passer les
     * tests ci-dessus sans rien prouver.
     */
    public function test_my_own_records_are_still_readable_and_writable(): void
    {
        $mienne = $this->role(settings()->id);

        $this->assertNotNull(app(RoleInterface::class)->get($mienne->id));
        $this->assertTrue(app(RoleInterface::class)->update($mienne->id, new Request([
            'name' => 'Agent de quai', 'permissions' => ['parcel_read'], 'status' => 1,
        ])));
        $this->assertSame('Agent de quai', $mienne->fresh()->name);

        $monHub = $this->hub(settings()->id);
        $this->assertNotNull(app(HubInterface::class)->get($monHub->id));
        $this->assertTrue(app(HubInterface::class)->update($monHub->id, new Request([
            'name' => 'Hub Cotonou', 'phone' => '0022997000123', 'address' => 'Akpakpa',
            'lat' => '6.36', 'long' => '2.42', 'status' => 1,
        ])));
        $this->assertSame('Hub Cotonou', $monHub->fresh()->name);
    }


    /**
     * Le garde-fou du test lui-même : une table de cas vide ferait passer tout ce
     * qui précède sans rien exercer.
     */
    public function test_the_case_table_really_covers_the_eleven_repositories(): void
    {
        $this->assertCount(11, $this->cas());
    }

    /**
     * Les suppressions de ces mêmes dépôts. Elles étaient déjà **gardées** — la
     * forme « je cherche nu, puis je compare `company_id` » — mais rien ne
     * l'inscrivait.
     */
    public function test_no_delete_of_these_repositories_reaches_another_company(): void
    {
        $supprimees = [];

        $cas = [
            'role' => [\App\Models\Backend\Role::class, RoleInterface::class, $this->role(self::AUTRE)->id],
            'asset' => [Asset::class, AssetInterface::class, $this->asset(self::AUTRE)->id],
            'assetcategory' => [Assetcategory::class, AssetCategoryInterface::class, $this->assetCategory(self::AUTRE)->id],
            'designation' => [Designation::class, DesignationInterface::class, $this->designation(self::AUTRE)->id],
            'hub' => [Hub::class, HubInterface::class, $this->hub(self::AUTRE)->id],
            'delivery_charge' => [DeliveryCharge::class, DeliveryChargeInterface::class, $this->deliveryCharge(self::AUTRE)->id],
            'todo' => [To_do::class, TodoInterface::class, $this->todo(self::AUTRE)->id],
            'department' => [Department::class, DepartmentInterface::class, $this->department(self::AUTRE)->id],
            'hub_payment' => [HubPayment::class, HubPaymentInterface::class, $this->hubPayment(self::AUTRE)->id],
        ];

        foreach ($cas as $nom => [$modele, $interface, $id]) {
            app($interface)->delete($id);

            if (blank($modele::withoutGlobalScopes()->find($id))) {
                $supprimees[] = $nom;
            }
        }

        $this->assertSame([], $supprimees, "Lignes d'une autre société supprimées :\n - " . implode("\n - ", $supprimees));
    }

    /**
     * 🔴 Les versements aux entrepôts : trois chemins qui DÉPLACENT DE L'ARGENT
     * sur une lecture nue.
     *
     * `cancelProcess()` **crédite notre compte bancaire** du montant lu, et
     * `processed()` le **débite** — sur le versement d'une autre société, on
     * encaissait donc son montant, ou on décaissait le nôtre pour solder le sien.
     *
     * ⚠️ `processed()` prend son identifiant dans le **corps** de la requête,
     * pas dans l'URL : `WebIsolationCoverageTest` ne pouvait pas le voir. C'est
     * l'angle mort du filet, et voilà ce qu'il cachait.
     */
    public function test_no_hub_payment_of_another_company_moves_money(): void
    {
        $sien = $this->versementDecaisse(self::AUTRE);
        $statutAvant = (int) $sien->fresh()->status;
        $soldeVoisinAvant = (float) \App\Models\Backend\Account::find($sien->from_account)->balance;

        // Un compte a NOUS, pour que `processed()` ait tout ce qu'il lui faut :
        // sans cela il echouerait de lui-meme et le refus ne prouverait rien.
        $monCompte = \App\Models\Backend\Account::forceCreate([
            'company_id' => settings()->id, 'balance' => 900000,
            'account_holder_name' => 'Mon compte', 'account_no' => '999',
        ]);
        $transactionsAvant = \App\Models\Backend\BankTransaction::count();

        $depot = app(HubPaymentInterface::class);

        $this->assertFalse((bool) $depot->cancelProcess($sien->id), 'cancelProcess a agi hors périmètre');
        $this->assertFalse((bool) $depot->processed(new Request([
            'id' => $sien->id, 'from_account' => $monCompte->id, 'transaction_id' => 'X',
        ])), 'processed a agi hors périmètre');
        $this->assertFalse((bool) $depot->reject($sien->id));
        $this->assertFalse((bool) $depot->cancelReject($sien->id));

        $this->assertSame($transactionsAvant, \App\Models\Backend\BankTransaction::count(),
            'une écriture bancaire a été passée sur le versement d\'une autre société');
        $this->assertSame($statutAvant, (int) $sien->fresh()->status,
            'le statut du versement d\'une autre société a changé');
        $this->assertNotNull(HubPayment::withoutGlobalScopes()->find($sien->id));

        $this->assertSame($soldeVoisinAvant, (float) \App\Models\Backend\Account::find($sien->from_account)->balance,
            'le solde du compte bancaire de l\'autre société a bougé');
        $this->assertSame(900000.0, (float) $monCompte->fresh()->balance,
            'notre compte a été débité pour solder le versement d\'une autre société');
    }


    /**
     * Les deux ecrans qui lisent hors depot, directement dans le controleur.
     *
     * `HubPaymentController::process()` faisait `HubPayment::findOrFail($id)` NU —
     * l'ecran qui precede le decaissement s'ouvrait sur le versement d'une autre
     * societe. `HubController::view()`, lui, passe par `parcelFilter()`, deja
     * `companywise()` : on l'inscrit sans le corriger.
     */
    public function test_the_two_screens_that_read_outside_a_repository_are_scoped(): void
    {
        $sien = $this->hubPayment(self::AUTRE);

        // ⚠️ Le controleur enveloppe sa lecture dans un `try/catch (\Exception)` et
        // rend `redirect()->back()` : hors perimetre il ne LEVE pas, il redirige.
        // Ce qu'on tient donc, c'est qu'aucune VUE ne part avec le versement du
        // voisin — c'est la fuite, et c'est ce que le sabotage doit rouvrir.
        $reponse = app(\App\Http\Controllers\Backend\HubPaymentController::class)->process($sien->id);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $reponse,
            'l\'ecran de decaissement s\'est ouvert sur le versement d\'une autre societe');

        // Controle negatif : sur le NOTRE, la vue part bien.
        $mien = $this->hubPayment(settings()->id);
        $this->assertInstanceOf(\Illuminate\View\View::class,
            app(\App\Http\Controllers\Backend\HubPaymentController::class)->process($mien->id));

        $colisDuVoisin = \App\Models\Backend\Parcel::forceCreate([
            'company_id' => self::AUTRE, 'merchant_id' => \App\Models\Backend\Merchant::firstOrFail()->id,
            'hub_id' => $this->hub(self::AUTRE)->id, 'tracking_id' => 'BL-VOISIN',
            'customer_name' => 'Client du voisin', 'status' => 1, 'cash_collection' => 10000,
        ]);

        $vues = app(\App\Repositories\Hub\HubInterface::class)
            ->parcelFilter(new Request(), $colisDuVoisin->hub_id)->get();

        $this->assertEmpty($vues, 'la fiche d\'un entrepot d\'une autre societe liste ses colis');
    }

    /**
     * Et deux routes MORTES retirees au passage : `assets/view/{id}` et
     * `asset-category/view/{id}` pointaient vers une methode `view()` qui n'existe
     * sur aucun des deux controleurs — elles repondaient 500 a chaque appel.
     * Aucune vue, aucun script ne referencait leurs noms. Meme constat que les cinq
     * routes mortes de S26 : le test tient LES DEUX moities, pour qu'on ne puisse
     * pas le « refermer » en ajoutant une methode vide.
     */
    public function test_the_two_dead_asset_view_routes_are_gone(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString("assets/view/{id}", $routes);
        $this->assertStringNotContainsString("asset-category/view/{id}", $routes);

        $this->assertFalse(method_exists(\App\Http\Controllers\Backend\AssetController::class, 'view'));
        $this->assertFalse(method_exists(\App\Http\Controllers\Backend\AssetcategoryController::class, 'view'));
    }

    /* ───────────────────────── la table des cas ─────────────────────────── */

    private function cas(): array
    {
        $role = $this->role(self::AUTRE);
        $asset = $this->asset(self::AUTRE);
        $categorie = $this->assetCategory(self::AUTRE);
        $designation = $this->designation(self::AUTRE);
        $emballage = $this->packaging(self::AUTRE);
        $hub = $this->hub(self::AUTRE);
        $bareme = $this->deliveryCharge(self::AUTRE);
        $tache = $this->todo(self::AUTRE);
        $annonce = $this->newsOffer(self::AUTRE);
        $departement = $this->department(self::AUTRE);
        $versement = $this->hubPayment(self::AUTRE);

        return [
            'role' => [Role::class, $role, fn () => app(RoleInterface::class)->update($role->id,
                new Request(['name' => 'Volé', 'permissions' => ['parcel_read'], 'status' => 1]))],
            'asset' => [Asset::class, $asset, fn () => app(AssetInterface::class)->update(
                new Request(['id' => $asset->id, 'name' => 'Volé', 'assetcategory_id' => 1, 'quantity' => 1]))],
            'assetcategory' => [Assetcategory::class, $categorie, fn () => app(AssetCategoryInterface::class)->update(
                new Request(['id' => $categorie->id, 'title' => 'Volé', 'position' => 1]))],
            'designation' => [Designation::class, $designation, fn () => app(DesignationInterface::class)->update(
                $designation->id, new Request(['title' => 'Volé', 'status' => 1]))],
            'packaging' => [Packaging::class, $emballage, fn () => app(PackagingInterface::class)->update(
                new Request(['id' => $emballage->id, 'name' => 'Volé', 'price' => 1, 'status' => 1, 'position' => 1]))],
            'hub' => [Hub::class, $hub, fn () => app(HubInterface::class)->update($hub->id,
                new Request(['name' => 'Volé', 'phone' => '1', 'address' => 'x', 'lat' => '1', 'long' => '1', 'status' => 1]))],
            'delivery_charge' => [DeliveryCharge::class, $bareme, fn () => app(DeliveryChargeInterface::class)->update(
                new Request(['id' => $bareme->id, 'category' => 1, 'weight' => 1, 'zone' => null, 'status' => 1]))],
            'todo' => [To_do::class, $tache, fn () => app(TodoInterface::class)->update(
                new Request(['id' => $tache->id, 'title' => 'Volé', 'description' => 'x',
                    'user_id' => User::where('company_id', settings()->id)->first()?->id, 'date' => now()->toDateString()]))],
            'news_offer' => [NewsOffer::class, $annonce, fn () => app(NewsOfferInterface::class)->update(
                $annonce->id, new Request(['title' => 'Volé', 'description' => 'x', 'status' => 1]))],
            'department' => [Department::class, $departement, fn () => app(DepartmentInterface::class)->update(
                $departement->id, new Request(['title' => 'Volé', 'status' => 1]))],
            'hub_payment' => [HubPayment::class, $versement, fn () => app(HubPaymentInterface::class)->update(
                $versement->id, new Request(['hub_id' => $versement->hub_id, 'amount' => 1]))],
        ];
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function agent(): User
    {
        $agent = new User();
        $agent->company_id = settings()->id;
        $agent->name = 'Agent S29';
        $agent->email = 'agent.vol@example.test';
        $agent->mobile = '0022997002222';
        $agent->password = bcrypt('secret');
        $agent->user_type = \App\Enums\UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function role(int $societe): Role
    {
        return Role::forceCreate(['company_id' => $societe, 'name' => 'Role de ' . $societe,
            'slug' => 'role-' . $societe, 'permissions' => ['parcel_read'], 'status' => 1]);
    }

    private function asset(int $societe): Asset
    {
        return Asset::forceCreate(['company_id' => $societe, 'name' => 'Bien de ' . $societe,
            'supplyer_name' => 'Fournisseur', 'quantity' => 3, 'amount' => 150000]);
    }

    private function assetCategory(int $societe): Assetcategory
    {
        return Assetcategory::forceCreate(['company_id' => $societe, 'title' => 'Categorie de ' . $societe, 'position' => 1]);
    }

    private function designation(int $societe): Designation
    {
        return Designation::forceCreate(['company_id' => $societe, 'title' => 'Poste de ' . $societe, 'status' => 1]);
    }

    private function packaging(int $societe): Packaging
    {
        return Packaging::forceCreate(['company_id' => $societe, 'name' => 'Emballage de ' . $societe,
            'price' => 500, 'status' => 1, 'position' => 1]);
    }

    private function hub(int $societe): Hub
    {
        return Hub::forceCreate(['company_id' => $societe, 'name' => 'Entrepot de ' . $societe,
            'phone' => '0022997009999', 'address' => 'Cotonou', 'status' => 1]);
    }

    private function deliveryCharge(int $societe): DeliveryCharge
    {
        return DeliveryCharge::forceCreate(['company_id' => $societe, 'category_id' => 1, 'weight' => 1]);
    }

    private function todo(int $societe): To_do
    {
        return To_do::forceCreate(['company_id' => $societe, 'title' => 'Tache de ' . $societe,
            'description' => 'x', 'user_id' => User::where('company_id', $societe)->firstOrFail()->id,
            'date' => now()->toDateString()]);
    }

    private function newsOffer(int $societe): NewsOffer
    {
        return NewsOffer::forceCreate(['company_id' => $societe, 'title' => 'Annonce de ' . $societe,
            'description' => 'x', 'status' => 1]);
    }

    private function department(int $societe): Department
    {
        return Department::forceCreate(['company_id' => $societe, 'title' => 'Service de ' . $societe, 'status' => 1]);
    }

    private function hubPayment(int $societe): HubPayment
    {
        return HubPayment::forceCreate(['company_id' => $societe,
            'hub_id' => $this->hub($societe)->id, 'amount' => 75000]);
    }

    /**
     * Un versement DEJA DECAISSE, avec son compte bancaire d'origine.
     *
     * ⚠️ Sans `from_account`, `cancelProcess()` echoue sur `Account::find(null)`
     * avant d'ecrire quoi que ce soit, et son `catch` rend `false` : mon premier
     * test passait donc pour la MAUVAISE raison — le sabotage du perimetre ne le
     * faisait pas rougir. La fixture doit rendre le chemin realisable pour que le
     * refus prouve le perimetre, et pas un plantage.
     */
    private function versementDecaisse(int $societe): HubPayment
    {
        $compte = \App\Models\Backend\Account::forceCreate([
            'company_id' => $societe,
            'balance' => 500000,
            'account_holder_name' => 'Compte de ' . $societe,
            'account_no' => '00' . $societe,
        ]);

        return HubPayment::forceCreate([
            'company_id' => $societe,
            'hub_id' => $this->hub($societe)->id,
            'amount' => 75000,
            'from_account' => $compte->id,
            'transaction_id' => 'TRX-' . $societe,
            'status' => \App\Enums\ApprovalStatus::PROCESSED,
        ]);
    }
}
