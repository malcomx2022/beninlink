<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\User;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S38 — la tache aveugle du filet : l'identifiant qui vit dans le CORPS.
 *
 * `WebIsolationCoverageTest` enumere les routes **a parametre d'URL**. C'est ce
 * qui lui donne sa liste de travail, et c'est aussi sa limite : une ecriture
 * dont l'identifiant voyage dans le corps de la requete n'apparait dans aucune
 * de ses quatre listes. Dix passes d'arriere ont bute cinq fois sur cette forme
 * — chaque fois par hasard, en suivant un appel depuis une autre piste.
 *
 * L'enumeration a donc porte sur les **controleurs**, pas sur les routes : 87
 * routes d'ecriture sans parametre lisent un identifiant dans le corps. Dans le
 * seul depot des colis, huit methodes n'avaient AUCUN perimetre — ni
 * `companywise()`, ni meme une comparaison de `company_id`.
 *
 * Sept d'entre elles sont des chemins **en lot**, et c'est ce qui les avait
 * gardees invisibles : leur identifiant n'est pas un identifiant, c'est une
 * **liste**. Rien dans la signature d'une route ne la porte.
 *
 * | Methode | Ce qu'un tableau d'identifiants etrangers obtenait |
 * |---|---|
 * | `transferToHubMultipleParcel` | statut `TRANSFER_TO_HUB` + `transfer_hub_id` vers NOTRE entrepot |
 * | `deliveryManAssignMultipleParcel` | colis d'autrui confies a NOTRE livreur, SMS a SON client |
 * | `pickupdatemanAssignedBulk` | idem cote ramassage |
 * | `AssignReturnToMerchantBulk` | retour au marchand + ecritures comptables |
 * | `parcelReceivedByMultipleHub` | `hub_id` reecrit : le colis change d'entrepot |
 * | `returnAssignToMerchant` | (identifiant unique, meme angle mort) frais de retour debite |
 * | `bulkParcels` | nom, telephone, adresse du client — alimente les vues de masse |
 * | `parcelMultiplePrintLabel` | les memes donnees, sur une etiquette imprimee |
 *
 * **La forme du correctif suit la logique du socle.** Dans un lot, un
 * identifiant hors perimetre est ignore et la boucle continue : c'est ce que
 * fait deja le socle quand une ligne manque, et cela evite qu'un identifiant
 * glisse par erreur dans une selection ne fasse echouer tout le lot d'un agent
 * legitime. Sur un chemin a identifiant unique, on refuse.
 *
 * Chaque test porte son **controle negatif** : le colis de la maison doit
 * vraiment passer. Sans cette moitie, une garde trop large — ou une fixture qui
 * ne remplit pas ses conditions — donnerait un vert qui ne prouve rien.
 */
class ParcelBulkScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private ParcelInterface $depot;
    private Parcel $monColis;
    private Parcel $sonColis;
    private DeliveryMan $monLivreur;
    private Hub $monEntrepot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monColis = $this->colisDe($this->marchandDe(settings()->id));
        $this->sonColis = $this->colisDe(Merchant::where('company_id', self::AUTRE)->firstOrFail());

        $this->monLivreur = $this->livreurDe(settings()->id);
        $this->monEntrepot = Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Entrepot S38', 'status' => 1,
        ]);

        $this->actingAs($this->agentDe(settings()->id));

        $this->depot = app(ParcelInterface::class);
    }

    /* ───────────────────────── les chemins en lot ───────────────────────── */

    public function test_bulk_transfer_to_hub_ignores_another_companys_parcels(): void
    {
        $this->assertTrue($this->depot->transferToHubMultipleParcel(new Request([
            'parcel_ids' => [$this->monColis->id, $this->sonColis->id],
            'hub_id' => $this->monEntrepot->id,
            'delivery_man_id' => $this->monLivreur->id,
            'note' => 'transfert',
        ])));

        $this->assertColisIntact($this->sonColis);

        // Contrôle négatif : le mien a bien été transféré.
        $this->assertSame(ParcelStatus::TRANSFER_TO_HUB, $this->monColis->fresh()->status);
        $this->assertSame($this->monEntrepot->id, $this->monColis->fresh()->transfer_hub_id);
    }

    public function test_bulk_deliveryman_assignment_ignores_another_companys_parcels(): void
    {
        $this->assertTrue($this->depot->deliveryManAssignMultipleParcel(new Request([
            'parcel_ids_' => [$this->monColis->id, $this->sonColis->id],
            'delivery_man_id' => $this->monLivreur->id,
            'note' => 'affectation',
        ])));

        $this->assertColisIntact($this->sonColis);

        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, $this->monColis->fresh()->status);
    }

    public function test_bulk_pickup_assignment_ignores_another_companys_parcels(): void
    {
        $this->assertTrue($this->depot->pickupdatemanAssignedBulk(new Request([
            'parcel_id' => [$this->monColis->id, $this->sonColis->id],
            'delivery_man_id' => $this->monLivreur->id,
            'note' => 'ramassage',
        ])));

        $this->assertColisIntact($this->sonColis);

        $this->assertSame(ParcelStatus::PICKUP_ASSIGN, $this->monColis->fresh()->status);
    }

    public function test_bulk_return_to_merchant_ignores_another_companys_parcels(): void
    {
        $this->assertTrue($this->depot->AssignReturnToMerchantBulk(new Request([
            'parcel_id' => [$this->monColis->id, $this->sonColis->id],
            'delivery_man_id' => $this->monLivreur->id,
            'note' => 'retour',
            'date' => now()->toDateString(),
        ])));

        $this->assertColisIntact($this->sonColis);

        $this->assertSame(ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, $this->monColis->fresh()->status);
    }

    /**
     * Celui-ci ne changeait pas seulement un statut : il **reecrivait
     * `hub_id`**. Le colis d'un autre transporteur atterrissait dans notre
     * entrepot, et son marchand voyait sa marchandise annoncee ailleurs.
     */
    public function test_bulk_received_by_hub_ignores_another_companys_parcels(): void
    {
        $this->sonColis->transfer_hub_id = $this->monEntrepot->id;
        $this->sonColis->save();

        $this->monColis->transfer_hub_id = $this->monEntrepot->id;
        $this->monColis->save();

        $identifiants = [$this->monColis->id, $this->sonColis->id];

        $this->assertTrue($this->depot->parcelReceivedByMultipleHub($identifiants, new Request([
            'parcel_id' => $identifiants,
            'note' => 'reception',
        ])));

        $this->assertColisIntact($this->sonColis);
        $this->assertNull($this->sonColis->fresh()->hub_id, 'le colis d\'en face a change d\'entrepot');

        $this->assertSame(ParcelStatus::RECEIVED_BY_HUB, $this->monColis->fresh()->status);
        $this->assertSame($this->monEntrepot->id, $this->monColis->fresh()->hub_id);
    }

    /* ────────────── l'identifiant unique, meme angle mort ───────────────── */

    public function test_return_assign_to_merchant_refuses_another_companys_parcel(): void
    {
        $this->assertFalse($this->depot->returnAssignToMerchant($this->sonColis->id, new Request([
            'delivery_man_id' => $this->monLivreur->id,
            'note' => 'retour',
            'date' => now()->toDateString(),
        ])));

        $this->assertColisIntact($this->sonColis);

        // Contrôle négatif : sur le mien, l'opération aboutit.
        $this->assertTrue($this->depot->returnAssignToMerchant($this->monColis->id, new Request([
            'delivery_man_id' => $this->monLivreur->id,
            'note' => 'retour',
            'date' => now()->toDateString(),
        ])));
        $this->assertSame(ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, $this->monColis->fresh()->status);
    }

    /* ─────────────────────────── les lectures ───────────────────────────── */

    public function test_bulk_read_only_returns_my_own_parcels(): void
    {
        $lot = $this->depot->bulkParcels([$this->monColis->id, $this->sonColis->id]);

        $this->assertSame([$this->monColis->id], $lot->pluck('id')->all());
    }

    /**
     * Les etiquettes sont le point de fuite le plus direct : elles portent en
     * clair le nom, le telephone et l'adresse du destinataire.
     */
    public function test_multiple_print_label_only_returns_my_own_parcels(): void
    {
        $etiquettes = $this->depot->parcelMultiplePrintLabel(new Request([
            'parcels' => [$this->monColis->id, $this->sonColis->id],
        ]));

        $this->assertSame([$this->monColis->id], $etiquettes->pluck('id')->all());
        $this->assertStringNotContainsString(
            'Client de la societe ' . self::AUTRE,
            $etiquettes->pluck('customer_name')->implode(' '),
        );
    }

    /* ──────────────────── les neuf annulations ─────────────────────────── */

    /**
     * Les annulations de statut forment la moitie la plus nombreuse de l'angle
     * mort : neuf routes `POST`, toutes lisant `$request->parcel_id`. Ce
     * qu'elles font n'est pas anodin — elles reculent le statut **et
     * SUPPRIMENT les evenements** correspondants. Or les evenements sont la
     * chronologie que le client d'en face consulte en suivant son colis :
     * l'effacement ne laisse aucune trace, chez personne.
     *
     * @dataProvider annulations
     */
    public function test_a_status_cancellation_refuses_another_companys_parcel(string $methode, int $statut): void
    {
        $this->placerEnStatut($this->sonColis, $statut);
        $this->placerEnStatut($this->monColis, $statut);

        $this->assertFalse(
            $this->depot->{$methode}($this->sonColis->id, new Request(['note' => 'annulation'])),
            $methode . ' accepte le colis d\'une autre societe',
        );

        $this->assertSame($statut, $this->sonColis->fresh()->status, $methode . ' a recule le statut d\'en face');
        $this->assertSame(
            1,
            ParcelEvent::where('parcel_id', $this->sonColis->id)->count(),
            $methode . ' a efface la chronologie d\'une autre societe',
        );

        // Contrôle négatif : sur le mien, l'annulation aboutit et l'evenement
        // correspondant disparait bien. Sans lui, une garde qui refuserait TOUT
        // passerait pour une preuve d'isolation.
        $this->assertTrue($this->depot->{$methode}($this->monColis->id, new Request(['note' => 'annulation'])));
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->monColis->id)->count());
    }

    public static function annulations(): array
    {
        return [
            'ramassage affecte' => ['pickupdatemanAssignedCancel', ParcelStatus::PICKUP_ASSIGN],
            'ramassage replanifie' => ['PickupReScheduleCancel', ParcelStatus::PICKUP_RE_SCHEDULE],
            'recu par le ramasseur' => ['receivedBypickupmanCancel', ParcelStatus::RECEIVED_BY_PICKUP_MAN],
            'transfert vers entrepot' => ['transfertoHubCancel', ParcelStatus::TRANSFER_TO_HUB],
            'recu par entrepot' => ['receivedByHubCancel', ParcelStatus::RECEIVED_BY_HUB],
            'livreur affecte' => ['deliverymanAssignCancel', ParcelStatus::DELIVERY_MAN_ASSIGN],
            'livraison replanifiee' => ['deliveryReScheduleCancel', ParcelStatus::DELIVERY_RE_SCHEDULE],
            'retour au transporteur' => ['returntoQourierCancel', ParcelStatus::RETURN_TO_COURIER],
            'retour marchand replanifie' => ['returnAssignToMerchantRescheduleCancel', ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE],
        ];
    }

    /** Un colis dans le statut vise, avec l'evenement que l'annulation cherche. */
    private function placerEnStatut(Parcel $colis, int $statut): void
    {
        $colis->status = $statut;
        $colis->save();

        $evenement = new ParcelEvent();
        $evenement->parcel_id = $colis->id;
        $evenement->parcel_status = $statut;
        $evenement->note = 'pose par la fixture';
        $evenement->created_by = auth()->id();
        $evenement->save();
    }

    /* ─────────────────────────── outillage ──────────────────────────────── */

    /**
     * Le colis d'en face n'a pas bouge — ni son statut, ni sa chronologie.
     *
     * La seconde moitie compte autant que la premiere : un evenement pose sur
     * le colis d'un autre transporteur apparait dans le suivi que consulte SON
     * client, meme si le statut, lui, avait ete remis en place.
     */
    private function assertColisIntact(Parcel $colis): void
    {
        $this->assertSame(
            ParcelStatus::PENDING,
            $colis->fresh()->status,
            'le statut du colis d\'une autre societe a change',
        );
        $this->assertSame(
            0,
            ParcelEvent::where('parcel_id', $colis->id)->count(),
            'un evenement a ete pose sur la chronologie d\'une autre societe',
        );
    }

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent lot';
        $agent->email = 'agent.lot.' . $societe . '@example.test';
        $agent->mobile = '00229970100' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand lot ' . $societe;
        $utilisateur->email = 'marchand.lot.' . $societe . '@example.test';
        $utilisateur->mobile = '00229970200' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME lot ' . $societe,
            'current_balance' => 0,
        ]);
    }

    private function livreurDe(int $societe): DeliveryMan
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Livreur lot ' . $societe;
        $utilisateur->email = 'livreur.lot.' . $societe . '@example.test';
        $utilisateur->mobile = '00229970300' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::DELIVERYMAN;
        $utilisateur->unique_id = 'L-LOT-' . $societe;
        $utilisateur->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => 500,
            'pickup_charge' => 200,
            'return_charge' => 300,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    private function colisDe(Merchant $marchand): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-LOT-' . $marchand->company_id . '-' . uniqid(),
            'customer_name' => 'Client de la societe ' . $marchand->company_id,
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => ParcelStatus::PENDING,
        ]);
    }
}
