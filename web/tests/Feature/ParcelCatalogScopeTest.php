<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Models\User;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\Parcel\DeliveryChargeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S46 — les catalogues d'un colis : le TROISIEME identifiant.
 *
 * S38 a ferme l'identifiant du colis. S45 a ferme celui de l'agent — le livreur
 * ou l'entrepot nomme au passage. La creation d'un colis en porte une troisieme
 * famille, plus nombreuse : les **catalogues** qu'on lui applique. Categorie,
 * boutique de ramassage, emballage, zone, delai — sept champs, tous lus dans le
 * corps de la requete, tous convertis en argent ou en adresse.
 *
 * ⚠️ **Ce lot corrige d'abord mon propre releve.** La cartographie de S45
 * annoncait ces sept identifiants comme « non gardes, seul `Merchant` l'est ».
 * La mesure dit autre chose, et c'est plus interessant :
 *
 * | Identifiant | Ce que la mesure a trouve |
 * |---|---|
 * | `zone_id` | **deja garde DEUX fois** — `ChargeCalculator` et la regle `DeliveryRoutePriced` |
 * | `delivery_type_id` | constante de plateforme (`DeliveryType`), pas une cle etrangere |
 * | `priority_id` | drapeau 1/2 ecrit en dur par le controleur, pas une cle |
 * | `delay_id` | **NU dans le resolveur** — le defaut d'argent de ce lot |
 * | `shop_id` | **NU** — et `merchant_shops` n'a pas de `company_id` (S26) |
 * | `packaging_id` | **NU a l'ecriture**, alors que son PRIX, lui, est garde |
 * | `category_id` | **NU** |
 *
 * Trois defauts, pas sept, et pas de la meme gravite. Les nommer separement
 * vaut mieux que d'annoncer « sept identifiants non gardes », qui aurait ete
 * plus spectaculaire et faux.
 */
class ParcelCatalogScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private ParcelInterface $depot;
    private Merchant $monMarchand;
    private Merchant $sonMarchand;
    private DeliveryZone $maZone;
    private DeliveryZone $saZone;
    private int $maCategorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monMarchand = $this->marchandDe(settings()->id);
        $this->sonMarchand = Merchant::where('company_id', self::AUTRE)->firstOrFail();

        $this->actingAs($this->agentDe(settings()->id));
        $this->depot = app(ParcelInterface::class);

        $this->tariferMaSociete();
    }

    /* ───────────── le delai : un supplement venu d'ailleurs ──────────────── */

    /**
     * `DeliveryChargeResolver::supplement()` lisait `DeliveryDelay::find()` nu.
     * Le delai porte une SURCHARGE, ajoutee au tarif de la course : nommer le
     * delai d'une autre societe facturait donc son supplement a notre marchand.
     *
     * C'est le seul des trois defauts qui touche directement l'argent, et il
     * est atteignable par deux chemins — la creation du colis et la regle de
     * validation `DeliveryRoutePriced`, qui passe elle aussi `delay_id` brut.
     */
    public function test_a_delay_of_another_company_never_surcharges_our_tariff(): void
    {
        $sienDelai = DeliveryDelay::forceCreate([
            'company_id' => self::AUTRE, 'code' => 'express_voisin', 'name' => 'Express du voisin',
            'surcharge' => 9000, 'position' => 1, 'status' => Status::ACTIVE,
        ]);

        $this->assertSame(
            1500.0,
            $this->tarif($sienDelai->id),
            'le supplement de delai d\'une autre societe a ete facture a notre marchand',
        );

        // Controle negatif : notre propre delai, lui, surcharge bien.
        $monDelai = DeliveryDelay::forceCreate([
            'company_id' => settings()->id, 'code' => 'express', 'name' => 'Express',
            'surcharge' => 250, 'position' => 1, 'status' => Status::ACTIVE,
        ]);

        $this->assertSame(1750.0, $this->tarif($monDelai->id));
    }

    /**
     * ⚠️ Le perimetre du supplement est celui de la societe de la ZONE, pas du
     * locataire ambiant — et ce test existe pour que personne ne « simplifie »
     * en `companywise()`.
     *
     * Le resolveur ne suppose nulle part que la route qu'on lui demande est
     * celle du locataire courant : `trancheDeZone()` ne s'appuie que sur le
     * marchand et la zone. Un tarif calcule pour une route de la societe 2
     * doit donner le meme montant quel que soit le contexte d'appel.
     *
     * Cette fixture n'est pas theorique : c'est exactement celle de
     * `DeliveryZoneGridTest`, ou `settings()->id` vaut 1 tandis que le premier
     * marchand appartient a la societe 2. Une garde sur `settings()` y avalait
     * silencieusement le supplement.
     */
    public function test_the_surcharge_follows_the_zone_company_not_the_ambient_tenant(): void
    {
        $this->assertNotSame(
            settings()->id,
            $this->sonMarchand->company_id,
            'la fixture doit partir d\'un marchand d\'une AUTRE societe que le locataire courant',
        );

        $saCategorie = Deliverycategory::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Standard voisin', 'status' => Status::ACTIVE, 'position' => 1,
        ]);
        $this->bareme(self::AUTRE, $saCategorie->id, $this->saZone->id, 1, 800);
        // Le locataire seme installe deja le catalogue des delais des deux
        // societes : on regle la surcharge de celui du voisin, on n'en cree
        // pas un second (la cle `company_id + code` est unique).
        $sonDelai = DeliveryDelay::where('company_id', self::AUTRE)
            ->where('code', DeliveryDelay::SAME_DAY)->firstOrFail();
        $sonDelai->surcharge = 300;
        $sonDelai->save();

        $this->assertSame(
            1100.0,
            app(DeliveryChargeResolver::class)->resolveByZone(
                $this->sonMarchand->id, $saCategorie->id, 1, $this->saZone->id, $sonDelai->id,
            ),
            'le supplement de la societe 2 a ete avale parce que le locataire ambiant est la societe 1',
        );
    }

    /* ────────── la boutique : une adresse qui n'est pas la notre ─────────── */

    /**
     * `merchant_shops` ne porte AUCUNE colonne `company_id` (constat de S26) :
     * son perimetre ne peut passer que par le marchand. Le depot ecrivait
     * `merchant_shop_id = $request->shop_id` sans rien verifier.
     *
     * La consequence est concrete et imprimee : `backend/parcel/bulk_print`
     * rend `$parcel->merchantShop->contact_no`. Le telephone de la boutique
     * d'un marchand d'une autre societe partait sur NOTRE etiquette.
     */
    public function test_a_shop_of_another_companys_merchant_is_refused(): void
    {
        $saBoutique = $this->boutiqueDe($this->sonMarchand);

        $this->assertFalse(
            (bool) $this->depot->store($this->requete(['shop_id' => $saBoutique->id])),
            'la boutique d\'un marchand d\'une autre societe a ete acceptee',
        );
        $this->assertSame(0, Parcel::count(), 'un colis a ete cree malgre la boutique etrangere');

        // Controle negatif : la boutique de NOTRE marchand est bien rattachee.
        $maBoutique = $this->boutiqueDe($this->monMarchand);
        $this->assertTrue((bool) $this->depot->store($this->requete(['shop_id' => $maBoutique->id])));
        $this->assertSame($maBoutique->id, Parcel::firstOrFail()->merchant_shop_id);
    }

    /**
     * Une boutique de la bonne societe mais d'un AUTRE marchand n'est pas
     * davantage la sienne : la garde passe par le marchand du colis, pas par
     * la societe. Sans cette moitie, un `whereHas('merchant', companywise())`
     * trop large passerait le test precedent sans rien empecher ici.
     */
    public function test_a_shop_of_another_merchant_of_our_own_company_is_refused(): void
    {
        $voisin = $this->marchandDe(settings()->id, 'voisin');
        $saBoutique = $this->boutiqueDe($voisin);

        $this->assertFalse((bool) $this->depot->store($this->requete(['shop_id' => $saBoutique->id])));
        $this->assertSame(0, Parcel::count());
    }

    /* ─────────── l'emballage : enregistre ailleurs, facture zero ─────────── */

    /**
     * Le PRIX de l'emballage etait deja garde — `ChargeCalculator::
     * packagingAmount()` fait `Packaging::companywise()->find()`. L'ECRITURE,
     * elle, ne l'etait pas. Un emballage etranger etait donc inscrit sur le
     * colis et facture **zero** : le colis portait un emballage absent de notre
     * catalogue, et personne ne le payait.
     */
    public function test_a_packaging_of_another_company_is_refused(): void
    {
        $sonEmballage = Packaging::forceCreate([
            'company_id' => self::AUTRE, 'name' => 'Carton du voisin', 'price' => 5000,
            'status' => Status::ACTIVE, 'position' => 1,
        ]);

        $this->assertFalse((bool) $this->depot->store($this->requete(['packaging_id' => $sonEmballage->id])));
        $this->assertSame(0, Parcel::count());

        // Controle negatif : le notre passe, ET il est facture.
        $monEmballage = Packaging::forceCreate([
            'company_id' => settings()->id, 'name' => 'Carton maison', 'price' => 300,
            'status' => Status::ACTIVE, 'position' => 1,
        ]);
        $this->assertTrue((bool) $this->depot->store($this->requete(['packaging_id' => $monEmballage->id])));

        $colis = Parcel::firstOrFail();
        $this->assertSame($monEmballage->id, (int) $colis->packaging_id);
        $this->assertSame(300.0, (float) $colis->packaging_amount);
    }

    /* ───────────── la categorie : un refus voulu, pas accidentel ─────────── */

    /**
     * ⚠️ La fixture de ce test demande une explication, sans quoi il ne
     * prouverait rien. Une categorie etrangere fait d'ordinaire echouer la
     * creation **par accident** : aucune ligne de bareme ne la porte, donc le
     * resolveur rend `null` et l'appelant refuse. Mesurer le refus dans cet
     * etat reviendrait a mesurer un faux deja acquis sans la garde.
     *
     * On pose donc une ligne de bareme de NOTRE societe qui porte la categorie
     * du voisin — rien dans le schema ne l'interdit, `delivery_charges` ne
     * croise pas ses deux cles. Sans la garde, le colis se creerait donc tres
     * bien, au nom d'une categorie qui n'est pas a notre catalogue.
     */
    public function test_a_category_of_another_company_is_refused_by_a_guard_not_by_accident(): void
    {
        $saCategorie = Deliverycategory::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Categorie du voisin', 'status' => Status::ACTIVE, 'position' => 9,
        ]);
        $this->bareme(settings()->id, $saCategorie->id, $this->maZone->id, 1, 1500);

        $this->assertFalse((bool) $this->depot->store($this->requete(['category_id' => $saCategorie->id])));
        $this->assertSame(0, Parcel::count());

        // Controle negatif : la notre passe, avec la meme requete par ailleurs.
        $this->assertTrue((bool) $this->depot->store($this->requete()));
    }

    /* ───────── les trois portes : creer, dupliquer, modifier ────────────── */

    /**
     * ⚠️ Ce test et le suivant existent parce que le sabotage les a reclames.
     * La garde est appelee dans TROIS methodes — `store`, `duplicateStore` et
     * `update` — et le lot ne couvrait que la premiere : neutraliser l'appel
     * dans les deux autres laissait la suite VERTE. Une garde posee trois fois
     * n'est pas une garde prouvee trois fois.
     */
    public function test_the_duplicate_of_a_parcel_refuses_a_foreign_shop(): void
    {
        $saBoutique = $this->boutiqueDe($this->sonMarchand);

        $this->assertFalse(
            (bool) $this->depot->duplicateStore($this->requete(['shop_id' => $saBoutique->id])),
            'la duplication a accepte la boutique d\'un marchand d\'une autre societe',
        );
        $this->assertSame(0, Parcel::count());

        // Controle negatif : la duplication passe sans catalogue etranger.
        $this->assertTrue((bool) $this->depot->duplicateStore($this->requete()));
        $this->assertSame(1, Parcel::count());
    }

    /** La modification d'un colis existant ferme la meme porte. */
    public function test_updating_a_parcel_refuses_a_foreign_packaging(): void
    {
        $this->assertTrue((bool) $this->depot->store($this->requete()));
        $colis = Parcel::firstOrFail();

        $sonEmballage = Packaging::forceCreate([
            'company_id' => self::AUTRE, 'name' => 'Carton du voisin', 'price' => 5000,
            'status' => Status::ACTIVE, 'position' => 1,
        ]);

        $this->assertFalse(
            (bool) $this->depot->update($colis->id, $this->requete(['packaging_id' => $sonEmballage->id])),
            'la modification a accepte un emballage d\'une autre societe',
        );
        $this->assertNull($colis->fresh()->packaging_id);

        // Controle negatif : le notre passe, et il est facture.
        $monEmballage = Packaging::forceCreate([
            'company_id' => settings()->id, 'name' => 'Carton maison', 'price' => 300,
            'status' => Status::ACTIVE, 'position' => 1,
        ]);
        $this->assertTrue((bool) $this->depot->update($colis->id, $this->requete(['packaging_id' => $monEmballage->id])));
        $this->assertSame($monEmballage->id, (int) $colis->fresh()->packaging_id);
    }

    /* ───────── la zone : ce que le releve de S45 annoncait a tort ────────── */

    /**
     * La cartographie de S45 rangeait `zone_id` parmi les identifiants nus.
     * C'etait faux, et ce test l'inscrit pour que personne n'aille « reparer »
     * ce qui tient deja : `ChargeCalculator` resout la zone en `companywise()`
     * et refuse quand elle n'est pas des notres.
     */
    public function test_the_zone_was_already_guarded_before_this_lot(): void
    {
        $this->assertFalse((bool) $this->depot->store($this->requete(['zone_id' => $this->saZone->id])));
        $this->assertSame(0, Parcel::count());
    }

    /* ───────────────────────────── fixtures ─────────────────────────────── */

    private function tarif(?int $delaiId): ?float
    {
        return app(DeliveryChargeResolver::class)->resolveByZone(
            $this->monMarchand->id,
            $this->maCategorie,
            1,
            $this->maZone->id,
            $delaiId,
        );
    }

    private function requete(array $enPlus = []): Request
    {
        return new Request($enPlus + [
            'merchant_id' => $this->monMarchand->id,
            'category_id' => $this->maCategorie,
            'zone_id' => $this->maZone->id,
            'weight' => 1,
            'invoice_no' => 'F-S46',
            'cash_collection' => 10000,
            'shop_id' => null,
            'pickup_phone' => '0022997000000',
            'pickup_address' => 'Cotonou',
            'customer_name' => 'Client S46',
            'customer_phone' => '0022990000000',
            'customer_address' => 'Cotonou',
            'delivery_type_id' => 1,
        ]);
    }

    private function tariferMaSociete(): void
    {
        $zones = app(\App\Services\Pricing\ZoneCatalog::class)->installer(settings()->id);
        $this->maZone = $zones[DeliveryZone::COTONOU];
        $this->saZone = app(\App\Services\Pricing\ZoneCatalog::class)
            ->installer(self::AUTRE)[DeliveryZone::COTONOU];

        $this->maCategorie = Deliverycategory::forceCreate([
            'company_id' => settings()->id, 'title' => 'Standard', 'status' => Status::ACTIVE, 'position' => 1,
        ])->id;

        $this->bareme(settings()->id, $this->maCategorie, $this->maZone->id, 1, 1500);
    }

    private function bareme(int $societe, int $categorie, int $zone, int $poids, int $montant): void
    {
        $tarif = new DeliveryCharge();
        $tarif->company_id = $societe;
        $tarif->category_id = $categorie;
        $tarif->zone_id = $zone;
        $tarif->weight = $poids;
        $tarif->amount = $montant;
        $tarif->position = $poids;
        $tarif->status = Status::ACTIVE;
        $tarif->save();
    }

    private function boutiqueDe(Merchant $marchand): MerchantShops
    {
        return MerchantShops::forceCreate([
            'merchant_id' => $marchand->id,
            'name' => 'Boutique de ' . $marchand->id,
            'contact_no' => '0022997' . str_pad((string) $marchand->id, 6, '0', STR_PAD_LEFT),
            'address' => 'Adresse ' . $marchand->id,
            'status' => Status::ACTIVE,
            'default_shop' => Status::INACTIVE,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent catalogue';
        $agent->email = 'agent.s46.' . $societe . '@example.test';
        $agent->mobile = '00229974100' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe, string $marque = 'a'): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand catalogue ' . $marque;
        $utilisateur->email = 'marchand.s46.' . $marque . '.' . $societe . '@example.test';
        $utilisateur->mobile = '0022997420' . $marque . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME catalogue ' . $marque . ' ' . $societe,
            'current_balance' => 0,
        ]);
    }
}
