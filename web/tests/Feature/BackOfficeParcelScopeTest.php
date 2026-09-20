<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\ParcelController;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S33 — les colis du back-office, huitième passe sur l'arriéré du filet.
 *
 * Le périmètre de lecture de ce dépôt est le plus élaboré du socle, et il est
 * **correct** : `get()` et `details()` filtrent par société **et** par l'entrepôt
 * de l'agent — mais seulement quand l'agent en a un. Un administrateur sans
 * `hub_id` voit tous les colis de sa société. J'avais annoncé ce point comme un
 * risque (« peut-être trop strict ») : vérifié, il ne l'est pas. Le test l'inscrit
 * dans les deux sens.
 *
 * Ce qui restait :
 *
 * | Point | Ce qu'il faisait |
 * |---|---|
 * | `update()` | 🔴 `Parcel::find($id)` **nu** — le colis d'une autre société se réécrivait |
 * | `update()` | 🔴 `Merchant::find($request->merchant_id)` **nu** — et la ligne suivante **réaffectait** le colis à un marchand d'ailleurs, en recopiant son `hub_id` |
 * | `duplicate`, `edit`, `parcelPrint`, `parcelPrintLabel` | déréférençaient `null` → **500** au lieu de 404 |
 * | `details` | rendait une page **vide avec un 200** : la vue déréférence `$parcel` avec l'opérateur `@`, qui supprime l'erreur |
 * | `destroy` | annonçait un succès sans regarder ce que le dépôt avait fait |
 *
 * ⚠️ **Un constat que j'ai cru trouver et qui n'en était pas un.**
 * `details()` construisait `ParcelEvent::where('parcel_id', $id)` avec
 * l'identifiant **brut**, sans périmètre — la forme exacte de S25. J'ai d'abord
 * cru à une troisième fuite de chronologie que S25 avait manquée. Vérification
 * faite : `details.blade.php` **n'utilise pas** `$parcelevents`. La requête était
 * calculée et jamais rendue — aucune fuite. Elle passe désormais par le colis déjà
 * vérifié, pour que la forme ne redevienne pas un piège, mais je ne présente pas
 * cela comme une faille fermée.
 */
class BackOfficeParcelScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private Merchant $monMarchand;
    private Merchant $sonMarchand;
    private Parcel $monColis;
    private Parcel $sonColis;
    private DeliveryZone $maZone;
    private int $maCategorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monMarchand = $this->marchandDe(settings()->id);
        $this->sonMarchand = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $this->monColis = $this->colisDe($this->monMarchand);
        $this->sonColis = $this->colisDe($this->sonMarchand);

        $this->actingAs($this->agentDe(settings()->id));

        $this->tariferMaSociete();
    }

    /* ─────────── le périmètre de lecture : correct, et pas trop strict ────── */

    public function test_an_administrator_without_a_hub_sees_all_parcels_of_his_company(): void
    {
        $depot = app(ParcelInterface::class);

        $this->assertNull(auth()->user()->hub_id, 'la fixture doit partir d\'un agent sans entrepôt');

        $this->assertNotNull($depot->get($this->monColis->id), 'un administrateur sans entrepôt ne voit plus ses colis');
        $this->assertNotNull($depot->details($this->monColis->id));
    }

    public function test_an_agent_bound_to_a_hub_only_sees_that_hubs_parcels(): void
    {
        $entrepot = \App\Models\Backend\Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Entrepot colis', 'status' => 1,
        ]);

        $chefDeHub = $this->agentDe(settings()->id, 'hub');
        $chefDeHub->hub_id = $entrepot->id;
        $chefDeHub->save();

        $this->actingAs($chefDeHub->fresh());

        $depot = app(ParcelInterface::class);

        // Mon colis n'est rattaché à aucun entrepôt : le chef de hub ne le voit pas.
        $this->assertNull($depot->get($this->monColis->id));

        // Celui de son entrepôt, si.
        $colisDuHub = $this->colisDe($this->monMarchand, $entrepot->id);
        $this->assertNotNull($depot->get($colisDuHub->id));
    }

    public function test_the_parcel_of_another_company_is_never_readable(): void
    {
        $depot = app(ParcelInterface::class);

        $this->assertNull($depot->get($this->sonColis->id));
        $this->assertNull($depot->details($this->sonColis->id));
    }

    /* ─────────────────────── les écritures ──────────────────────────────── */

    /**
     * 🔴 Le cœur du lot : `update()` lisait le colis ET le marchand cible sans
     * périmètre. On pouvait donc réécrire le colis d'une autre société **et** le
     * réaffecter à un marchand d'ailleurs.
     */
    public function test_the_parcel_of_another_company_cannot_be_rewritten(): void
    {
        $clientDorigine = $this->sonColis->customer_name;
        $marchandDorigine = $this->sonColis->merchant_id;

        $this->assertFalse((bool) app(ParcelInterface::class)->update(
            $this->sonColis->id,
            $this->requeteDeColis($this->monMarchand)
        ));

        $this->assertSame($clientDorigine, $this->sonColis->fresh()->customer_name);
        $this->assertSame($marchandDorigine, $this->sonColis->fresh()->merchant_id);
    }

    /** Et mon colis ne peut pas être réaffecté à un marchand d'une autre société. */
    public function test_my_parcel_cannot_be_reassigned_to_another_companys_merchant(): void
    {
        $marchandDorigine = $this->monColis->merchant_id;

        $this->assertFalse((bool) app(ParcelInterface::class)->update(
            $this->monColis->id,
            $this->requeteDeColis($this->sonMarchand)
        ));

        $this->assertSame($marchandDorigine, $this->monColis->fresh()->merchant_id,
            'le colis a été réaffecté au marchand d\'une autre société');
    }

    /**
     * 🔴 Relevé en corrigeant `update()` : le **même identifiant de marchand**
     * était lu nu sur les deux chemins de **création**. Le colis naissait dans ma
     * société — `company_id = settings()->id` — mais au nom du marchand d'une
     * autre, dont il recopiait l'entrepôt et à qui il débitait frais, TVA et net
     * à reverser. Aucun de ces deux chemins ne porte d'identifiant dans son URL :
     * ils sont donc **invisibles au filet**, qui n'énumère que les routes à
     * paramètre. C'est la même tache aveugle que les trois décaissements.
     */
    public function test_a_parcel_cannot_be_created_for_another_companys_merchant(): void
    {
        $depot = app(ParcelInterface::class);
        $avant = Parcel::withoutGlobalScopes()->count();

        $this->assertFalse((bool) $depot->store($this->requeteDeColis($this->sonMarchand)));
        $this->assertFalse((bool) $depot->duplicateStore(
            $this->requeteDeColis($this->sonMarchand, ['parcel_id' => $this->monColis->id])
        ));

        $this->assertSame($avant, Parcel::withoutGlobalScopes()->count(),
            'un colis a été créé au nom du marchand d\'une autre société');
    }

    /**
     * Le contrôle négatif du test précédent : avec **mon** marchand, les deux
     * chemins créent bien. Sans lui, un refus venu d'ailleurs — la tarification,
     * un solde, une colonne manquante — se lirait comme un périmètre qui tient.
     */
    public function test_both_creation_paths_still_work_for_my_own_merchant(): void
    {
        $depot = app(ParcelInterface::class);
        $avant = Parcel::withoutGlobalScopes()->count();

        $this->assertTrue((bool) $depot->store($this->requeteDeColis($this->monMarchand)));
        $this->assertTrue((bool) $depot->duplicateStore(
            $this->requeteDeColis($this->monMarchand, ['parcel_id' => $this->monColis->id])
        ));

        $this->assertSame($avant + 2, Parcel::withoutGlobalScopes()->count());
    }

    public function test_the_status_and_the_deletion_stay_in_scope(): void
    {
        $statutDorigine = (int) $this->sonColis->fresh()->status;

        $this->assertFalse((bool) app(ParcelInterface::class)->statusUpdate($this->sonColis->id, 5));
        $this->assertFalse((bool) app(ParcelInterface::class)->delete($this->sonColis->id));

        $this->assertSame($statutDorigine, (int) $this->sonColis->fresh()->status);
        $this->assertNotNull(Parcel::withoutGlobalScopes()->find($this->sonColis->id));
    }

    /* ─────────────────────── les six écrans ─────────────────────────────── */

    public function test_the_six_screens_answer_not_found_out_of_scope(): void
    {
        $controleur = app(ParcelController::class);
        $ouverts = [];

        $appels = [
            'details' => fn () => $controleur->details($this->sonColis->id),
            'edit' => fn () => $controleur->edit($this->sonColis->id),
            'clone' => fn () => $controleur->duplicate($this->sonColis->id),
            'print' => fn () => $controleur->parcelPrint($this->sonColis->id),
            'print/label' => fn () => $controleur->parcelPrintLabel($this->sonColis->id),
            'destroy' => fn () => $controleur->destroy($this->sonColis->id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans de colis ouverts sur le colis d'une autre société :\n - "
            . implode("\n - ", $ouverts));
    }

    /**
     * Le contrôle négatif des écrans : sur MON colis, ils rendent bien une vue.
     * Sans lui, un `abort_if` trop large passerait inaperçu.
     */
    public function test_my_own_parcel_still_opens_its_screens(): void
    {
        $controleur = app(ParcelController::class);

        foreach (['details', 'edit', 'duplicate', 'parcelPrint', 'parcelPrintLabel'] as $methode) {
            $this->assertInstanceOf(
                \Illuminate\View\View::class,
                $controleur->{$methode}($this->monColis->id),
                $methode . ' ne rend plus la vue de mon propre colis'
            );
        }
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    /**
     * Tarifer **ma** société, sinon les refus de `update()` ne prouveraient rien.
     *
     * Le jeu d'amorçage ne tarife que la société 2, et depuis **D4 étape 6**
     * `ChargeCalculator` **refuse** un colis sans zone tarifée
     * (`UnpricedDeliveryException`) — refus qui tombe *avant* le `save()`. Un test
     * écrit sans cette grille observait donc un refus venu de la tarification et
     * concluait à tort que le périmètre tenait : les deux sabotages du dépôt
     * passaient au vert. Un test de refus doit d'abord pouvoir réussir.
     */
    private function tariferMaSociete(): void
    {
        $this->maZone = app(\App\Services\Pricing\ZoneCatalog::class)
            ->installer(settings()->id)[DeliveryZone::COTONOU];

        $categorie = \App\Models\Backend\Deliverycategory::forceCreate([
            'company_id' => settings()->id, 'title' => 'Standard', 'status' => 1, 'position' => 1,
        ]);
        $this->maCategorie = $categorie->id;

        // `company_id` n'est pas assignable en masse sur ce modèle du socle.
        $tarif = new DeliveryCharge();
        $tarif->company_id = settings()->id;
        $tarif->category_id = $this->maCategorie;
        $tarif->zone_id = $this->maZone->id;
        $tarif->weight = 1;
        $tarif->amount = 1500;
        $tarif->position = 1;
        $tarif->status = 1;
        $tarif->save();
    }

    private function agentDe(int $societe, string $suffixe = 'principal'): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent colis ' . $suffixe;
        $agent->email = 'agent.colis.' . $suffixe . '.' . $societe . '@example.test';
        $agent->mobile = '0022997008' . $societe . strlen($suffixe);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand colis ' . $societe;
        $utilisateur->email = 'marchand.colis.' . $societe . '@example.test';
        $utilisateur->mobile = '0022997009' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME colis ' . $societe,
            'current_balance' => 0,
        ]);
    }

    private function colisDe(Merchant $marchand, ?int $entrepot = null): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'hub_id' => $entrepot,
            'tracking_id' => 'BL-' . $marchand->company_id . '-' . uniqid(),
            'customer_name' => 'Client de la societe ' . $marchand->company_id,
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => 1,
        ]);
    }

    /** Une requête de modification complète, avec le marchand cible. */
    private function requeteDeColis(Merchant $cible, array $enPlus = []): \Illuminate\Http\Request
    {
        return new \Illuminate\Http\Request($enPlus + [
            'merchant_id' => $cible->id,
            'category_id' => $this->maCategorie,
            'zone_id' => $this->maZone->id,
            'weight' => 1,
            'invoice_no' => 'F-1',
            'cash_collection' => 10000,
            'shop_id' => null,
            'pickup_phone' => '0022997000000',
            'pickup_address' => 'Ailleurs',
            'customer_name' => 'Client detourne',
            'customer_phone' => '0022990000000',
            'customer_address' => 'Ailleurs',
            'delivery_type_id' => 1,
        ]);
    }
}
