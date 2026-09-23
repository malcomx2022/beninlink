<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\MerchantDeliveryChargeController;
use App\Http\Controllers\MerchantPaymentAccountController;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\MerchantPayment;
use App\Models\User;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use App\Repositories\MerchantPayment\PaymentInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\MerchantShops;
use App\Repositories\MerchantShops\ShopsInterface;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S34 — la famille **marchand**, neuvième passe sur l'arriéré du filet.
 *
 * La plus sensible de l'arriéré, et de loin la plus ouverte. Trois sous-familles,
 * trois natures de dégât :
 *
 * | Sous-famille | Ce qui était atteignable chez un autre transporteur |
 * |---|---|
 * | **fiche marchand** | `update()` lisait `Merchant::find($id)` **nu** puis réécrivait l'e-mail **et le mot de passe** du compte marchand : reprise de compte complète |
 * | **comptes de versement** | tout le dépôt était nu — lecture de la banque, du titulaire et du **numéro de compte** ; suppression ; et remplacement du compte par le sien |
 * | **barèmes négociés** | `update()` lisait la ligne sans périmètre puis écrivait `company_id = settings()->id` : **reprise de ligne** ; `store()` acceptait n'importe quel marchand d'URL |
 *
 * ⚠️ **Quatre des écritures les plus graves n'ont pas d'identifiant dans leur
 * URL** : `paymentinfo/bank/store`, `mobile/store`, `bank/update`,
 * `mobile/update` portent le `merchant_id` et l'`editid` dans le **corps** de la
 * requête. Le filet ne les voit pas — il n'énumère que les routes à paramètre.
 * Quatrième occurrence de cette tache aveugle, et celle qui coûte le plus cher :
 * c'est là que se décide **où l'argent est versé**.
 *
 * ⚠️ `MerchantPayment::scopeCompanywise()` **mentait** : la table
 * `merchant_payments` n'a pas de colonne `company_id`. Le scope existait, aucun
 * appelant ne s'en servait, et il aurait levé une erreur SQL. Un test l'inscrit.
 */
class MerchantFamilyScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private Merchant $monMarchand;
    private Merchant $sonMarchand;
    private MerchantPayment $monCompte;
    private MerchantPayment $sonCompte;
    private DeliveryCharge $maGrille;
    private DeliveryCharge $saGrille;
    private MerchantDeliveryCharge $monBareme;
    private MerchantDeliveryCharge $sonBareme;
    private int $monEntrepot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monMarchand = $this->marchandDe(settings()->id);
        $this->sonMarchand = Merchant::where('company_id', self::AUTRE)->firstOrFail();

        $this->actingAs($this->agentDe(settings()->id));

        // Le jeu d'amorçage n'installe entrepôt, grille et zones que pour la
        // société 2 : la mienne a besoin des siens, sinon les refus observés
        // viendraient d'une fixture incomplète et non du périmètre.
        $this->monEntrepot = \App\Models\Backend\Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Entrepot S34', 'status' => 1,
        ])->id;

        $this->maGrille = $this->grilleDe(settings()->id);
        $this->saGrille = DeliveryCharge::where('company_id', self::AUTRE)->firstOrFail();

        $this->monCompte = $this->compteDe($this->monMarchand, '0001111111');
        $this->sonCompte = $this->compteDe($this->sonMarchand, '0009999999');

        $this->monBareme = $this->baremeDe($this->monMarchand, $this->maGrille);
        $this->sonBareme = $this->baremeDe($this->sonMarchand, $this->saGrille);
    }

    /* ═══════════ le piège du modèle : un scope qui interrogeait le vide ═════ */

    public function test_the_payout_table_carries_no_company_and_the_scope_goes_through_the_merchant(): void
    {
        $this->assertFalse(Schema::hasColumn('merchant_payments', 'company_id'),
            'si la colonne existe désormais, ce scope doit être réécrit pour la lire');

        // Et le scope réparé fonctionne : il ne lève plus, et il sépare.
        $ids = MerchantPayment::companywise()->pluck('id')->all();

        $this->assertContains($this->monCompte->id, $ids);
        $this->assertNotContains($this->sonCompte->id, $ids);
    }

    /* ═══════════════════ les comptes de versement ══════════════════════════ */

    public function test_the_payout_accounts_of_another_companys_merchant_are_not_readable(): void
    {
        $depot = app(PaymentInterface::class);

        $this->assertCount(0, $depot->get($this->sonMarchand->id),
            'le numéro de compte d\'un marchand d\'un autre transporteur était lisible');
        $this->assertNull($depot->edit($this->sonCompte->id));

        // Contrôle négatif : le mien reste lisible.
        $this->assertCount(1, $depot->get($this->monMarchand->id));
        $this->assertNotNull($depot->edit($this->monCompte->id));
    }

    public function test_the_payout_account_of_another_company_cannot_be_deleted(): void
    {
        $depot = app(PaymentInterface::class);

        $this->assertFalse((bool) $depot->delete($this->sonCompte->id));
        $this->assertNotNull(MerchantPayment::find($this->sonCompte->id));

        // Contrôle négatif : le mien se supprime bien.
        $this->assertTrue((bool) $depot->delete($this->monCompte->id));
        $this->assertNull(MerchantPayment::find($this->monCompte->id));
    }

    /**
     * 🔴 Le cœur du lot. `editid` **détruit** la ligne désignée avant d'en créer
     * une neuve avec le `merchant_id` du formulaire. Sans périmètre, l'écran
     * « ajouter un compte » devenait « remplacer le compte du voisin par le mien ».
     */
    public function test_a_payout_account_cannot_be_taken_over_from_another_company(): void
    {
        $depot = app(PaymentInterface::class);
        $numeroDorigine = $this->sonCompte->account_no;

        $formes = [
            'bankstore' => fn () => $depot->bankstore($this->requeteDeCompte($this->sonMarchand, $this->sonCompte)),
            'mobilestore' => fn () => $depot->mobilestore($this->requeteDeCompte($this->sonMarchand, $this->sonCompte)),
            'bankUpdate' => fn () => $depot->bankUpdate($this->requeteDeCompte($this->sonMarchand, $this->sonCompte)),
            'mobileUpdate' => fn () => $depot->mobileUpdate($this->requeteDeCompte($this->sonMarchand, $this->sonCompte)),
        ];

        foreach ($formes as $nom => $appel) {
            $this->assertFalse((bool) $appel(), $nom . ' a accepté le marchand d\'une autre société');
        }

        $survivant = MerchantPayment::find($this->sonCompte->id);

        $this->assertNotNull($survivant, 'son compte de versement a été détruit');
        $this->assertSame($numeroDorigine, $survivant->account_no,
            'le numéro de versement d\'un autre transporteur a été réécrit');
        $this->assertSame(1, MerchantPayment::where('merchant_id', $this->sonMarchand->id)->count(),
            'un compte de versement a été ajouté chez le marchand d\'une autre société');
    }

    /**
     * Le cas que mon premier jet n'exerçait pas, et le sabotage l'a dit : un
     * `merchant_id` d'une autre société **sans** `editid`. Les tests précédents
     * passaient tous un `editid`, dont le garde refusait d'abord — le périmètre du
     * marchand n'était jamais atteint, et le retirer laissait la suite au vert.
     * C'est une création pure : rien à détruire, juste un compte de versement
     * ajouté chez le marchand d'un autre transporteur.
     */
    public function test_no_payout_account_can_be_added_to_another_companys_merchant(): void
    {
        $depot = app(PaymentInterface::class);
        $avant = MerchantPayment::where('merchant_id', $this->sonMarchand->id)->count();

        $sansEditid = new Request($this->requeteDeCompte($this->sonMarchand)->all());
        $sansEditid->merge(['editid' => null]);

        $this->assertFalse((bool) $depot->bankstore($sansEditid));
        $this->assertFalse((bool) $depot->mobilestore($sansEditid));

        $this->assertSame($avant, MerchantPayment::where('merchant_id', $this->sonMarchand->id)->count(),
            'un compte de versement a été ajouté chez le marchand d\'une autre société');
    }

    /**
     * Et la variante la plus discrète : un `merchant_id` qui est bien le mien,
     * mais un `editid` qui désigne la ligne du voisin. Sans le second contrôle,
     * l'écriture détruisait sa ligne et créait la mienne.
     */
    public function test_a_foreign_row_cannot_be_destroyed_through_my_own_merchant(): void
    {
        $depot = app(PaymentInterface::class);

        $requete = $this->requeteDeCompte($this->monMarchand, $this->sonCompte);

        $this->assertFalse((bool) $depot->bankstore($requete));
        $this->assertFalse((bool) $depot->bankUpdate($requete));

        $this->assertNotNull(MerchantPayment::find($this->sonCompte->id),
            'sa ligne a été détruite par un `editid` non vérifié');
    }

    /**
     * Le contrôle négatif des quatre écritures : sur MON marchand et MA ligne,
     * elles fonctionnent. Sans lui, un refus venu d'ailleurs — une validation,
     * une colonne absente — se lirait comme un périmètre qui tient.
     */
    public function test_the_four_payout_writes_still_work_on_my_own_merchant(): void
    {
        $depot = app(PaymentInterface::class);

        $this->assertTrue((bool) $depot->bankstore($this->requeteDeCompte($this->monMarchand)));
        $this->assertTrue((bool) $depot->mobilestore($this->requeteDeCompte($this->monMarchand)));

        $aMoi = MerchantPayment::where('merchant_id', $this->monMarchand->id)->orderByDesc('id')->firstOrFail();

        $this->assertTrue((bool) $depot->bankUpdate($this->requeteDeCompte($this->monMarchand, $aMoi)));
        $aMoi = MerchantPayment::where('merchant_id', $this->monMarchand->id)->orderByDesc('id')->firstOrFail();
        $this->assertTrue((bool) $depot->mobileUpdate($this->requeteDeCompte($this->monMarchand, $aMoi)));
    }

    /**
     * ⚠️ Le cas que le sabotage a reclame sur les deux MISES A JOUR : `editid`
     * qui est bien le mien, `merchant_id` qui ne l'est pas. Les tests
     * precedents passaient toujours un `editid` etranger, dont la garde de la
     * LIGNE refusait d'abord — le perimetre du MARCHAND n'etait jamais atteint,
     * et le retirer laissait `bankUpdate` et `mobileUpdate` au vert.
     *
     * C'est la meme forme que partout depuis S45 : la ressource est gardee, et
     * c'est la garde de la ressource qui masque celle du second identifiant.
     */
    public function test_a_payout_account_cannot_be_moved_to_another_companys_merchant(): void
    {
        $depot = app(PaymentInterface::class);

        $this->assertTrue((bool) $depot->bankstore($this->requeteDeCompte($this->monMarchand)));
        $maLigne = MerchantPayment::where('merchant_id', $this->monMarchand->id)->orderByDesc('id')->firstOrFail();

        $detournee = $this->requeteDeCompte($this->sonMarchand, $maLigne);

        $this->assertFalse((bool) $depot->bankUpdate($detournee));
        $this->assertFalse((bool) $depot->mobileUpdate($detournee));

        $this->assertSame(
            $this->monMarchand->id,
            (int) $maLigne->fresh()->merchant_id,
            'mon compte de versement a ete rattache au marchand d\'une autre societe',
        );
    }

    /* ─────────────── la boutique, et le marchand qui la porte ───────────── */

    /**
     * ⚠️ `ShopsRepository::store()` n'avait AUCUNE garde : `merchant_id` venait
     * du formulaire et partait tel quel dans la colonne. Un operateur creait
     * donc une boutique chez le marchand d'une AUTRE societe — et
     * `merchant_shops` ne porte pas de `company_id` (S26), donc cette boutique
     * vit entierement sous le marchand d'en face.
     */
    public function test_a_shop_cannot_be_created_for_another_companys_merchant(): void
    {
        $depot = app(ShopsInterface::class);
        $avant = MerchantShops::where('merchant_id', $this->sonMarchand->id)->count();

        $this->assertFalse((bool) $depot->store($this->requeteDeBoutique($this->sonMarchand)));

        $this->assertSame($avant, MerchantShops::where('merchant_id', $this->sonMarchand->id)->count(),
            'une boutique a ete creee chez le marchand d\'une autre societe');

        // Controle negatif : chez le mien, elle se cree.
        $this->assertTrue((bool) $depot->store($this->requeteDeBoutique($this->monMarchand)));
        $this->assertSame(1, MerchantShops::where('merchant_id', $this->monMarchand->id)->count());
    }

    /**
     * ⚠️ Et la mise a jour porte le trou que S29 avait NOMME sans le fermer.
     * Son commentaire dit : « la ligne `merchant_id` juste en dessous permettait
     * en plus de la RATTACHER a un autre marchand » — le correctif n'a ferme
     * que la LECTURE de la boutique. La ligne, elle, est restee.
     */
    public function test_a_shop_cannot_be_reassigned_to_another_companys_merchant(): void
    {
        $depot = app(ShopsInterface::class);

        $this->assertTrue((bool) $depot->store($this->requeteDeBoutique($this->monMarchand)));
        $maBoutique = MerchantShops::where('merchant_id', $this->monMarchand->id)->orderByDesc('id')->firstOrFail();

        $detournee = $this->requeteDeBoutique($this->sonMarchand);
        $detournee->merge(['id' => $maBoutique->id]);

        $this->assertFalse((bool) $depot->update($detournee));

        $this->assertSame(
            $this->monMarchand->id,
            (int) $maBoutique->fresh()->merchant_id,
            'ma boutique a ete rattachee au marchand d\'une autre societe',
        );
    }

    public function test_the_four_payout_screens_answer_not_found_out_of_scope(): void
    {
        $controleur = app(MerchantPaymentAccountController::class);
        $ouverts = [];

        $appels = [
            'payment/index' => fn () => $controleur->index($this->sonMarchand->id),
            'payment/add' => fn () => $controleur->paymentAdd($this->sonMarchand->id),
            'payment/edit' => fn () => $controleur->paymentEdit($this->sonMarchand->id, $this->sonCompte->id),
            'paymentmethod/change' => fn () => $controleur->paymentChange(
                new Request(['merchant_id' => $this->sonMarchand->id, 'payment_method' => 'bank'])
            ),
            'paymentinfo/delete' => fn () => $controleur->destroy($this->sonCompte->id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans de versement ouverts hors périmètre :\n - "
            . implode("\n - ", $ouverts));
    }

    /** Et sur mon marchand, ils rendent bien leur vue. */
    public function test_my_own_payout_screens_still_open(): void
    {
        $controleur = app(MerchantPaymentAccountController::class);

        $this->assertInstanceOf(\Illuminate\View\View::class, $controleur->index($this->monMarchand->id));
        $this->assertInstanceOf(\Illuminate\View\View::class, $controleur->paymentAdd($this->monMarchand->id));
        $this->assertInstanceOf(\Illuminate\View\View::class,
            $controleur->paymentEdit($this->monMarchand->id, $this->monCompte->id));
    }

    /* ═══════════════════ les barèmes négociés ══════════════════════════════ */

    /** 🔴 Reprise de ligne : le tarif négocié du voisin devenait le nôtre. */
    public function test_the_negotiated_tariff_of_another_company_cannot_be_taken_over(): void
    {
        $depot = app(MerchantDeliveryChargeInterface::class);
        $montantDorigine = $this->sonBareme->amount;

        $this->assertFalse((bool) $depot->update(
            $this->requeteDeBareme($this->maGrille),
            $this->sonBareme->id,
            $this->sonMarchand->id
        ));

        $survivant = MerchantDeliveryCharge::find($this->sonBareme->id);

        $this->assertSame(self::AUTRE, (int) $survivant->company_id,
            'sa ligne de barème a été rapatriée dans ma société');
        $this->assertSame($montantDorigine, $survivant->amount);
    }

    public function test_a_negotiated_tariff_cannot_be_created_for_another_companys_merchant(): void
    {
        $depot = app(MerchantDeliveryChargeInterface::class);
        $avant = MerchantDeliveryCharge::where('merchant_id', $this->sonMarchand->id)->count();

        $this->assertFalse((bool) $depot->store($this->requeteDeBareme($this->maGrille), $this->sonMarchand->id));

        $this->assertSame($avant, MerchantDeliveryCharge::where('merchant_id', $this->sonMarchand->id)->count(),
            'un tarif négocié a été créé chez le marchand d\'une autre société');
    }

    /** La troisième lecture : la ligne de grille référencée. */
    public function test_a_negotiated_tariff_cannot_reference_another_companys_grid(): void
    {
        $depot = app(MerchantDeliveryChargeInterface::class);

        $this->assertFalse((bool) $depot->store($this->requeteDeBareme($this->saGrille), $this->monMarchand->id));
        $this->assertFalse((bool) $depot->update(
            $this->requeteDeBareme($this->saGrille),
            $this->monBareme->id,
            $this->monMarchand->id
        ));
    }

    public function test_the_negotiated_tariff_of_another_company_cannot_be_deleted(): void
    {
        $depot = app(MerchantDeliveryChargeInterface::class);

        $this->assertFalse((bool) $depot->delete($this->sonBareme->id, $this->sonMarchand->id));
        $this->assertNotNull(MerchantDeliveryCharge::find($this->sonBareme->id));

        // Et le `merchant_id` de l'URL n'est plus ignoré : ma ligne ne se
        // supprime pas par l'écran d'un autre de mes marchands.
        $unAutreDesMiens = $this->marchandDe(settings()->id, 'bis');
        $this->assertFalse((bool) $depot->delete($this->monBareme->id, $unAutreDesMiens->id));
        $this->assertNotNull(MerchantDeliveryCharge::find($this->monBareme->id));

        // Contrôle négatif : par le bon écran, elle se supprime.
        $this->assertTrue((bool) $depot->delete($this->monBareme->id, $this->monMarchand->id));
    }

    /**
     * ⚠️ Constat **D4** relevé au passage, distinct du périmètre : cet écran ne
     * posait pas `zone_id`. Depuis l'étape 6, `DeliveryChargeResolver` cherche la
     * ligne négociée **par zone** — une ligne sans zone n'est donc jamais trouvée,
     * et le tarif négocié à la main ne facturait rien. `beninlink:tarification-prete`
     * compte d'ailleurs ces lignes comme un blocage de déploiement.
     */
    public function test_a_negotiated_tariff_carries_the_zone_of_the_grid_line_it_references(): void
    {
        $this->assertNotNull($this->maGrille->zone_id, 'la fixture doit partir d\'une ligne de grille zonée');

        $this->assertTrue((bool) app(MerchantDeliveryChargeInterface::class)
            ->store($this->requeteDeBareme($this->maGrille), $this->monMarchand->id));

        $cree = MerchantDeliveryCharge::where('merchant_id', $this->monMarchand->id)
            ->orderByDesc('id')->firstOrFail();

        $this->assertSame((int) $this->maGrille->zone_id, (int) $cree->zone_id,
            'sans zone, le tarif négocié n\'est jamais retenu par le résolveur');
    }

    public function test_the_negotiated_tariff_screens_answer_not_found_out_of_scope(): void
    {
        $controleur = app(MerchantDeliveryChargeController::class);
        $ouverts = [];

        $appels = [
            'delivery-charge/index' => fn () => $controleur->index($this->sonMarchand->id),
            'delivery-charge/create' => fn () => $controleur->create($this->sonMarchand->id),
            'delivery-charge/edit' => fn () => $controleur->edit($this->sonMarchand->id, $this->sonBareme->id),
            'delivery-charge/delete' => fn () => $controleur->delete($this->sonMarchand->id, $this->sonBareme->id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans de barème négocié ouverts hors périmètre :\n - "
            . implode("\n - ", $ouverts));
    }

    /**
     * L'AJAX qui renseigne le formulaire : son identifiant vient du **corps**,
     * donc le filet ne le voit pas. Il rendait la catégorie, la tranche et le
     * **montant** d'une ligne de grille de n'importe quel transporteur.
     */
    public function test_the_grid_lookup_ajax_does_not_answer_for_another_companys_line(): void
    {
        $controleur = app(MerchantDeliveryChargeController::class);

        // ⚠️ Ce contrôleur teste `request()->ajax()` — la requête **globale**, pas
        // celle qu'on lui passe en argument. Un premier jet lui transmettait une
        // requête AJAX construite à la main : la condition était fausse, la méthode
        // rendait `''` sans rien lire, et le sabotage de sa lecture passait au vert.
        // Il faut donc lier la requête au conteneur pour atteindre le code utile.
        $this->assertSame('', $controleur->deliveryChargeInfo($this->ajaxDeGrille($this->saGrille)));

        // Contrôle négatif : sur MA ligne de grille, l'AJAX rend bien sa vue.
        $rendu = $controleur->deliveryChargeInfo($this->ajaxDeGrille($this->maGrille));

        $this->assertNotSame('', $rendu, 'l\'AJAX ne répond plus pour ma propre grille');
    }

    /** Une requête AJAX **liée au conteneur**, seule forme que ce contrôleur voit. */
    private function ajaxDeGrille(DeliveryCharge $grille): Request
    {
        $requete = Request::create('/', 'POST', ['delivery_charge_id' => $grille->id],
            [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        app()->instance('request', $requete);

        return $requete;
    }

    /* ═══════════════════════ la fiche marchand ═════════════════════════════ */

    /** 🔴 La reprise de compte : e-mail et mot de passe réécrits. */
    public function test_the_merchant_of_another_company_cannot_be_rewritten(): void
    {
        $sonUtilisateur = User::findOrFail($this->sonMarchand->user_id);
        $courrielDorigine = $sonUtilisateur->email;
        $motDePasseDorigine = $sonUtilisateur->password;

        $this->assertFalse((bool) app(MerchantInterface::class)
            ->update($this->sonMarchand->id, $this->requeteDeMarchand()));

        $apres = User::findOrFail($this->sonMarchand->user_id);

        $this->assertSame($courrielDorigine, $apres->email,
            'l\'e-mail du compte d\'un marchand d\'une autre société a été réécrit');
        $this->assertSame($motDePasseDorigine, $apres->password,
            'le mot de passe du compte d\'un marchand d\'une autre société a été réécrit');
    }

    public function test_the_merchant_of_another_company_cannot_be_deleted(): void
    {
        $this->assertFalse((bool) app(MerchantInterface::class)->delete($this->sonMarchand->id));
        $this->assertNotNull(Merchant::find($this->sonMarchand->id));
    }

    /** Contrôle négatif : ma fiche se modifie et se supprime bien. */
    public function test_my_own_merchant_can_still_be_updated_and_deleted(): void
    {
        $depot = app(MerchantInterface::class);

        $this->assertTrue((bool) $depot->update($this->monMarchand->id, $this->requeteDeMarchand()));
        $this->assertSame('nouveau.courriel@example.test',
            User::findOrFail($this->monMarchand->user_id)->email);

        $this->assertTrue((bool) $depot->delete($this->monMarchand->id));
        $this->assertNull(Merchant::find($this->monMarchand->id));
    }

    /**
     * ⚠️ Deuxième constat relevé au passage : aucun des **trois** chemins de
     * création d'un marchand ne posait `company_id` sur les barèmes négociés
     * qu'il crée. `MerchantDeliveryCharge::companywise()` ne les voyait donc
     * jamais : le marchand naissait avec un barème invisible dans son écran.
     */
    public function test_the_tariffs_created_with_a_merchant_are_visible_in_their_screen(): void
    {
        $this->assertGreaterThan(0, DeliveryCharge::companywise()->count(),
            'la fixture doit partir d\'une grille de société non vide');

        $this->assertTrue((bool) app(MerchantInterface::class)->store($this->requeteDeCreation()));

        $cree = Merchant::companywise()->where('business_name', 'PME creee S34')->firstOrFail();

        $this->assertGreaterThan(
            0,
            app(MerchantDeliveryChargeInterface::class)->getAll($cree->id)->count(),
            'les barèmes créés avec le marchand restent invisibles dans son écran',
        );
    }

    /* ═════════════════════════════ fixtures ════════════════════════════════ */

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent marchand';
        $agent->email = 'agent.marchand.' . $societe . '@example.test';
        $agent->mobile = '0022997010' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe, string $suffixe = 'principal'): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand ' . $suffixe;
        $utilisateur->email = 'marchand.' . $suffixe . '.' . $societe . '@example.test';
        $utilisateur->mobile = '0022997011' . $societe . strlen($suffixe);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME ' . $suffixe . ' ' . $societe,
            'current_balance' => 0,
            'opening_balance' => 0,
        ]);
    }

    private function grilleDe(int $societe): DeliveryCharge
    {
        $zone = app(\App\Services\Pricing\ZoneCatalog::class)
            ->installer($societe)[\App\Models\Backend\DeliveryZone::COTONOU];

        $tarif = new DeliveryCharge();
        $tarif->company_id = $societe;
        $tarif->category_id = 1;
        $tarif->zone_id = $zone->id;
        $tarif->weight = 1;
        $tarif->amount = 1500;
        $tarif->position = 1;
        $tarif->status = 1;
        $tarif->save();

        return $tarif;
    }

    private function compteDe(Merchant $marchand, string $numero): MerchantPayment
    {
        return MerchantPayment::forceCreate([
            'merchant_id' => $marchand->id,
            'payment_method' => 'bank',
            'bank_name' => 'Banque de la societe ' . $marchand->company_id,
            'holder_name' => $marchand->business_name,
            'account_no' => $numero,
            'branch_name' => 'Cotonou',
            'routing_no' => '12345',
            'status' => 1,
        ]);
    }

    private function baremeDe(Merchant $marchand, DeliveryCharge $grille): MerchantDeliveryCharge
    {
        return MerchantDeliveryCharge::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'delivery_charge_id' => $grille->id,
            'category_id' => $grille->category_id,
            'zone_id' => $grille->zone_id,
            'weight' => $grille->weight,
            'amount' => 999,
            'status' => 1,
        ]);
    }

    /** Une écriture de compte de versement : marchand visé, ligne visée. */
    private function requeteDeBoutique(Merchant $cible): Request
    {
        return new Request([
            'merchant_id' => $cible->id,
            'name' => 'Boutique S49',
            'contact_no' => '0022997000049',
            'address' => 'Cotonou',
            'lat' => '6.36',
            'long' => '2.42',
            'status' => 1,
        ]);
    }

    private function requeteDeCompte(Merchant $cible, ?MerchantPayment $ligne = null): Request
    {
        return new Request([
            'merchant_id' => $cible->id,
            'editid' => $ligne?->id,
            'payment_method_name' => 'bank',
            'bank_name' => 'Ma banque',
            'holder_name' => 'Moi',
            'account_no' => '0000000042',
            'branch_name' => 'Ailleurs',
            'routing_no' => '99999',
            'mobile_holder_name' => 'Moi',
            'mobile_company' => 'MTN',
            'mobile_no' => '0022990000042',
            'account_type' => 'personal',
            'status' => 1,
        ]);
    }

    private function requeteDeBareme(DeliveryCharge $grille): Request
    {
        return new Request([
            'delivery_charge_id' => $grille->id,
            'amount' => 4242,
            'status' => 1,
        ]);
    }

    private function requeteDeMarchand(): Request
    {
        return new Request([
            'name' => 'Nom detourne',
            'business_name' => 'PME detournee',
            'email' => 'nouveau.courriel@example.test',
            'password' => 'motdepasse-choisi',
            'mobile' => '0022997999999',
            'address' => 'Ailleurs',
            'hub' => $this->monEntrepot,
            'status' => 1,
            'area' => ['inside_city'],
            'charge' => ['inside_city' => 2],
            'vat' => 18,
            'payment_period' => 7,
            'wallet_use_activation' => 0,
        ]);
    }

    private function requeteDeCreation(): Request
    {
        return new Request([
            'name' => 'Marchand neuf',
            'business_name' => 'PME creee S34',
            'email' => 'pme.creee.s34@example.test',
            'password' => 'motdepasse',
            'mobile' => '0022997888888',
            'address' => 'Cotonou',
            'hub' => $this->monEntrepot,
            'status' => 1,
            'area' => ['inside_city'],
            'charge' => ['inside_city' => 2],
            'opening_balance' => '',
            'vat' => 18,
            'payment_period' => 7,
            'wallet_use_activation' => 0,
        ]);
    }
}
