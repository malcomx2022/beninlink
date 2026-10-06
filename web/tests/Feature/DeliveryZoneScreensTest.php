<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Services\Parcel\DeliveryChargeResolver;
use App\Services\Pricing\ZoneCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D4, étape 4** — les écrans de saisie.
 *
 * Les étapes 1 à 3 ont donné le schéma, la résolution et la conversion. Il
 * manquait le geste : seule `beninlink:zones-tarifaires` savait écrire ces
 * tables, et **rien** ne savait saisir les forfaits CEDEAO — précisément la
 * valeur que le métier doit encore fixer. Une décision qu'aucun écran ne peut
 * enregistrer n'est pas appliquée, elle est en attente.
 *
 * Ce que ces tests tiennent :
 *
 *  - le **code** d'une zone se fixe à la création et ne bouge plus (il porte
 *    le rattachement du taux COD) ;
 *  - une zone qui porte des tarifs **ne se supprime pas** — `zone_id` est en
 *    `nullOnDelete`, la supprimer changerait des tarifs sans le dire ;
 *  - la saisie reste **scopée par société** (S7) ;
 *  - la grille n'écrit que des lignes zonées : les quatre colonnes héritées ne
 *    bougent pas, donc une installation qui ne saisit rien facture comme avant.
 */
class DeliveryZoneScreensTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    private int $categoryId;

    private DeliveryZoneInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();

        // Le jeu d'amorçage donne au marchand un barème négocié complet, qui
        // l'emporte sur celui de la société. Ces tests parlent de la grille de
        // la société : on écarte le négocié pour que ce soit bien elle qui
        // réponde.
        \App\Models\Backend\MerchantDeliveryCharge::query()->delete();
        $this->categoryId = DeliveryCategory::firstOrFail()->id;

        // `settings()` retombe sur la société 1 hors requête : on ouvre une
        // session, comme le fait le back-office.
        Auth::login($this->merchant->user);

        $this->repo = app(DeliveryZoneInterface::class);
    }

    private function societe(): int
    {
        return (int) $this->merchant->company_id;
    }

    // ---- Les zones ---------------------------------------------------------

    public function test_la_saisie_cree_une_zone_et_fige_son_code(): void
    {
        // Le jeu d'amorçage installe les quatre zones ; ce test parle de la
        // **saisie**, il repart donc de rien.
        DeliveryCharge::where('company_id', $this->societe())->delete();
        DeliveryZone::where('company_id', $this->societe())->delete();

        $resultat = $this->repo->enregistrerZones([
            ['id' => '', 'name' => 'Cotonou', 'status' => Status::ACTIVE],
        ]);

        $this->assertSame(1, $resultat['ecrites']);

        $zone = DeliveryZone::where('company_id', $this->societe())->firstOrFail();
        $this->assertSame('cotonou', $zone->code);
        $this->assertSame('Cotonou', $zone->name);

        // Renommer ne touche pas au code : `ChargeCalculator::codRateForZone()`
        // reconnaît la zone par son code, pas par son libellé.
        $this->repo->enregistrerZones([
            ['id' => $zone->id, 'name' => 'Grand Cotonou', 'status' => Status::ACTIVE],
        ]);

        $zone->refresh();
        $this->assertSame('cotonou', $zone->code);
        $this->assertSame('Grand Cotonou', $zone->name);
        $this->assertSame(1, DeliveryZone::where('company_id', $this->societe())->count());
    }

    public function test_une_zone_qui_porte_des_tarifs_nest_pas_supprimee(): void
    {
        $cotonou = DeliveryZone::where('company_id', $this->societe())
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();
        $lignes = DeliveryCharge::where('zone_id', $cotonou->id)->count();
        $this->assertGreaterThan(0, $lignes);

        $resultat = $this->repo->enregistrerZones([
            ['id' => $cotonou->id, 'name' => $cotonou->name, 'delete' => '1'],
        ]);

        $this->assertSame(['Cotonou'], $resultat['refusees']);
        $this->assertSame(0, $resultat['supprimees']);
        $this->assertNotNull(DeliveryZone::find($cotonou->id));
        // Les tarifs seraient devenus des lignes héritées portant un `amount`
        // que plus personne ne lit : le refus les garde entiers.
        $this->assertSame($lignes, DeliveryCharge::where('zone_id', $cotonou->id)->count());
    }

    public function test_une_zone_sans_tarif_se_supprime(): void
    {
        $this->repo->enregistrerZones([['id' => '', 'name' => 'Zone d essai']]);
        $zone = DeliveryZone::where('company_id', $this->societe())->firstOrFail();

        $resultat = $this->repo->enregistrerZones([
            ['id' => $zone->id, 'name' => $zone->name, 'delete' => '1'],
        ]);

        $this->assertSame(1, $resultat['supprimees']);
        $this->assertSame([], $resultat['refusees']);
        $this->assertNull(DeliveryZone::find($zone->id));
    }

    public function test_la_saisie_ignore_la_zone_dune_autre_societe(): void
    {
        $voisine = GeneralSettings::findOrFail($this->societe())->replicate();
        $voisine->name = 'Transporteur voisin';
        $voisine->save();

        $etrangere = DeliveryZone::create([
            'company_id' => $voisine->id,
            'code' => 'cotonou',
            'name' => 'Cotonou du voisin',
            'position' => 0,
            'status' => Status::ACTIVE,
        ]);

        $this->repo->enregistrerZones([
            ['id' => $etrangere->id, 'name' => 'Renommée par un tiers', 'delete' => '1'],
        ]);

        $etrangere->refresh();
        $this->assertSame('Cotonou du voisin', $etrangere->name);
        $this->assertNotNull(DeliveryZone::find($etrangere->id));
    }

    // ---- Les délais --------------------------------------------------------

    public function test_le_supplement_saisi_est_global_a_toutes_les_zones(): void
    {

        $jourMeme = DeliveryDelay::where('company_id', $this->societe())
            ->where('code', DeliveryDelay::SAME_DAY)->firstOrFail();

        $this->repo->enregistrerDelais([
            ['id' => $jourMeme->id, 'name' => 'Jour même', 'surcharge' => 300, 'status' => Status::ACTIVE],
        ]);

        $resolveur = new DeliveryChargeResolver();
        $zones = DeliveryZone::where('company_id', $this->societe())
            ->whereIn('code', [DeliveryZone::COTONOU, DeliveryZone::PERIPHERIE, DeliveryZone::INTERIEUR])
            ->get();

        foreach ($zones as $zone) {
            $sans = $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $zone->id);
            $avec = $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $zone->id, $jourMeme->id);

            // Le même supplément partout : c'est ce qui distingue le nouveau
            // modèle des quatre colonnes, où il valait 200 F ici et 500 F là.
            $this->assertSame(300.0, $avec - $sans, "zone {$zone->code}");
        }
    }

    // ---- Les forfaits CEDEAO ----------------------------------------------

    public function test_le_forfait_dun_pays_se_saisit_et_devient_le_tarif(): void
    {
        $cedeao = $this->repo->zoneExport();
        $this->assertNotNull($cedeao);

        $resolveur = new DeliveryChargeResolver();
        // La Guinée (le Ghana a son forfait depuis S106) n'est pas dans les forfaits tranchés par le métier : avant
        // saisie, la zone ne sait rien lui facturer — et le dit.
        $this->assertNull(
            $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $cedeao->id, null, 'GN')
        );

        $this->repo->enregistrerPays($cedeao, [
            ['id' => '', 'code' => 'gn', 'name' => 'Guinée', 'flat_amount' => 14000, 'status' => Status::ACTIVE],
        ]);

        $pays = DeliveryZoneCountry::where('zone_id', $cedeao->id)->where('code', 'GN')->firstOrFail();
        $this->assertSame('Guinée', $pays->name);

        // Le forfait ne regarde pas le poids : 1 kg et 10 kg au même prix.
        $this->assertSame(14000.0, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $cedeao->id, null, 'GN'));
        $this->assertSame(14000.0, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 10, $cedeao->id, null, 'gn'));

        // Un pays toujours non saisi reste sans tarif : on ne facture pas au hasard.
        $this->assertNull(
            $resolveur->resolveByZone($this->merchant->id, $this->categoryId, 1, $cedeao->id, null, 'LR')
        );
    }

    public function test_un_pays_saisi_deux_fois_ne_fait_pas_doublon(): void
    {
        $cedeao = $this->repo->zoneExport();

        $this->repo->enregistrerPays($cedeao, [
            ['id' => '', 'code' => 'GN', 'name' => 'Guinée', 'flat_amount' => 14000],
            ['id' => '', 'code' => 'gn', 'name' => 'Guinée', 'flat_amount' => 15000],
        ]);

        $guinee = DeliveryZoneCountry::where('zone_id', $cedeao->id)->where('code', 'GN')->get();
        $this->assertCount(1, $guinee);
        $this->assertSame(15000.0, (float) $guinee->first()->flat_amount);
    }

    // ---- La grille ---------------------------------------------------------

    public function test_la_grille_ecrit_une_ligne_par_zone(): void
    {
        $poids = (int) DeliveryCharge::where('company_id', $this->societe())
            ->orderBy('weight')->firstOrFail()->weight;

        $this->repo->enregistrerZones([
            ['id' => '', 'name' => 'Cotonou'],
            ['id' => '', 'name' => 'Périphérie'],
        ]);
        $zones = $this->repo->zones();

        $ecrites = $this->repo->enregistrerGrille($this->categoryId, [
            ['weight' => $poids, 'amounts' => [
                $zones[0]->id => 900,
                $zones[1]->id => 1600,
            ]],
        ]);

        $this->assertSame(2, $ecrites);

        $resolveur = new DeliveryChargeResolver();
        $this->assertSame(900.0, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, $poids, $zones[0]->id));
        $this->assertSame(1600.0, $resolveur->resolveByZone($this->merchant->id, $this->categoryId, $poids, $zones[1]->id));

        // Chaque ligne écrite porte sa zone : depuis l'étape 6, une ligne de
        // barème sans zone n'a plus de sens — `beninlink:tarification-prete`
        // la signalerait comme une impasse.
        $this->assertSame(0, DeliveryCharge::where('company_id', $this->societe())
            ->where('category_id', $this->categoryId)->whereNull('zone_id')->count());
    }

    public function test_la_grille_est_rejouable_sans_doublon(): void
    {
        $this->repo->enregistrerZones([['id' => '', 'name' => 'Cotonou']]);
        $zone = $this->repo->zones()->firstOrFail();

        foreach ([700, 800] as $montant) {
            $this->repo->enregistrerGrille($this->categoryId, [
                ['weight' => 1, 'amounts' => [$zone->id => $montant]],
            ]);
        }

        $lignes = DeliveryCharge::where('zone_id', $zone->id)->where('weight', 1)->get();
        $this->assertCount(1, $lignes);
        $this->assertSame(800.0, (float) $lignes->first()->amount);
        $this->assertSame($this->societe(), (int) $lignes->first()->company_id);
    }

    /**
     * Retirer une tranche retire **toutes** ses lignes de zone, et celles-là
     * seulement : les autres tranches restent.
     */
    public function test_retirer_une_tranche_ne_touche_pas_les_autres(): void
    {
        $poids = (int) DeliveryCharge::where('company_id', $this->societe())
            ->orderBy('weight')->firstOrFail()->weight;
        $autres = DeliveryCharge::where('company_id', $this->societe())
            ->where('category_id', $this->categoryId)
            ->where('weight', '!=', $poids)->count();

        $this->repo->enregistrerGrille($this->categoryId, [['weight' => $poids, 'delete' => '1']]);

        $this->assertSame(0, DeliveryCharge::where('company_id', $this->societe())
            ->where('category_id', $this->categoryId)
            ->where('weight', $poids)->count());
        $this->assertSame($autres, DeliveryCharge::where('company_id', $this->societe())
            ->where('category_id', $this->categoryId)
            ->where('weight', '!=', $poids)->count());
    }

    // ---- L'écran de saisie d'une ligne, au passage -------------------------

    public function test_lecran_dedition_nouvre_plus_le_bareme_dune_autre_societe(): void
    {
        $voisine = GeneralSettings::findOrFail($this->societe())->replicate();
        $voisine->name = 'Transporteur voisin';
        $voisine->save();

        $etrangere = DeliveryCharge::forceCreate([
            'company_id' => $voisine->id,
            'category_id' => $this->categoryId,
            'weight' => 1,
            'amount' => 1,
            'position' => 1,
            'status' => Status::ACTIVE,
        ]);

        // S8 : `get()` était le seul accès non scopé du module.
        $this->assertNull(app(DeliveryChargeInterface::class)->get($etrangere->id));
    }

    public function test_une_categorie_sans_poids_se_saisit_a_la_tranche_zero(): void
    {
        $this->repo->enregistrerZones([['id' => '', 'name' => 'Cotonou']]);
        $zone = $this->repo->zones()->firstOrFail();

        // `delivery_charges.weight` est NOT NULL, défaut 0 : les catégories qui
        // ne se pèsent pas s'écrivent à la tranche 0. Laisser passer un `null`
        // ferait échouer l'enregistrement sur une contrainte, au `save()`.
        $this->repo->enregistrerGrille($this->categoryId, [
            ['amounts' => [$zone->id => 1200]],
        ]);
        $this->assertSame(1, DeliveryCharge::where('zone_id', $zone->id)->where('weight', 0)->count());

        $this->repo->enregistrerGrille($this->categoryId, [['weight' => '', 'delete' => '1']]);
        $this->assertSame(0, DeliveryCharge::where('zone_id', $zone->id)->where('weight', 0)->count());
    }

    public function test_les_routes_des_ecrans_empruntent_les_permissions_du_bareme(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        // Les six routes des deux écrans, avec la permission qu'elles portent.
        // `PermissionSeeder` fait des `new Permission()` sans garde d'unicité :
        // introduire `delivery_zone_*` dupliquerait les lignes d'une
        // installation existante et rendrait l'écran inaccessible à tous.
        $attendu = [
            "delivery-zone.index" => 'delivery_charge_read',
            "delivery-zone.zones" => 'delivery_charge_update',
            "delivery-zone.delays" => 'delivery_charge_update',
            "delivery-zone.countries" => 'delivery_charge_update',
            "delivery-zone.grid" => 'delivery_charge_read',
            "delivery-zone.grid.update" => 'delivery_charge_update',
        ];

        foreach ($attendu as $nom => $permission) {
            $motif = "/name\('" . preg_quote($nom, '/') . "'\)->middleware\('hasPermission:" . $permission . "'\)/";
            $this->assertMatchesRegularExpression($motif, $routes, "route {$nom}");
        }
    }

    /**
     * Le défaut d'origine : sur la liste du barème, « jour même » et
     * « lendemain » étaient **interverties** — l'écran annonçait un tarif et en
     * affichait un autre. Les quatre colonnes ont disparu à l'étape 6, mais la
     * garde reste : ce qui est annoncé en en-tête doit être ce qui est affiché
     * en cellule, dans le même ordre.
     */
    public function test_la_liste_affiche_chaque_valeur_sous_son_intitule(): void
    {
        $vue = file_get_contents(resource_path('views/backend/delivery_charge/index.blade.php'));

        preg_match_all("/__\('delivery_zone\.(zone|amount)'\)/", $vue, $entetes);
        preg_match_all('/\$delivery_charge->(zone|amount)/', $vue, $cellules);

        $this->assertSame(['zone', 'amount'], $entetes[1], "l'ordre des en-têtes a changé");
        $this->assertSame($entetes[1], $cellules[1], 'une valeur est affichée sous le mauvais intitulé');

        // Et plus aucune trace des quatre colonnes retirées.
        $this->assertDoesNotMatchRegularExpression('/same_day|next_day|sub_city|outside_city/', $vue);
    }
}
