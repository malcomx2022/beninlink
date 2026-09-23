<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
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
 * S45 — l'agent nomme : le SECOND identifiant d'un mouvement de colis.
 *
 * Un changement de statut porte deux identifiants dans le corps de la requete,
 * et pas un seul : le colis qu'on deplace, et **l'agent ou l'entrepot qu'on
 * nomme au passage**. S38 a ferme le premier axe. Le second est reste ouvert,
 * y compris sur les cinq chemins que S38 avait inscrits comme *prouves* — la
 * preuve portait sur le colis, et ne disait rien du livreur.
 *
 * C'est la troisieme fois que ce projet bute sur la meme forme : une surface
 * fermee sur un axe n'est pas fermee sur l'autre. S41 l'avait montre entre
 * droit et societe, S43 dans l'autre sens. Ici c'est entre **la ressource
 * deplacee** et **l'agent paye pour la deplacer**.
 *
 * | Methode | Etat avant S45 | Ce qu'un identifiant etranger obtenait |
 * |---|---|---|
 * | `returnAssignToMerchant` | S38 : colis prouve | solde du livreur d'une AUTRE societe **credite**, deux releves a `company_id` = la notre |
 * | `AssignReturnToMerchantBulk` | S38 : colis prouve | idem, une fois par colis du lot |
 * | `deliveryManAssignMultipleParcel` | S38 : colis prouve | livreur etranger nomme sur nos colis |
 * | `pickupdatemanAssignedBulk` | S38 : colis prouve | idem cote ramassage |
 * | `transferToHubMultipleParcel` | S38 : colis prouve | `hub_id` etranger sur tout le lot |
 * | `transfertohub` | arriere S38 | idem, un colis |
 * | `receivedWarehouse` | arriere S38 | `hub_id` etranger |
 * | `returnAssignToMerchantReschedule` | arriere S38 | livreur etranger nomme |
 *
 * **Les deux identifiants ne coutent pas la meme chose, et le test le dit.**
 * Un `delivery_man_id` etranger est une ecriture comptable qui FRANCHIT la
 * frontiere : nos livres creditent l'employe d'une autre societe. Un `hub_id`
 * etranger, lui, ne montre rien a l'autre societe — les listes d'entrepot
 * restent `companywise()` — mais il fait sortir NOTRE colis de toutes NOS
 * listes d'entrepot : il devient introuvable a nos propres agents. Les deux
 * sont des defauts ; ce ne sont pas les memes, et on ne les plaide pas pareil.
 *
 * **La forme du refus.** Dans un lot, S38 ignore un colis hors perimetre et
 * poursuit la boucle : l'identifiant est par element. L'agent, lui, vaut pour
 * TOUT le lot — un livreur etranger ne peut etre ignore sans vider le lot de
 * son sens. On refuse donc l'appel entier, comme sur un chemin unitaire.
 *
 * Chaque test porte son controle negatif : l'agent de la maison doit vraiment
 * passer. Sans cette moitie, une garde trop large donnerait un vert creux.
 */
class ParcelAgentScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private ParcelInterface $depot;
    private Parcel $monColis;
    private DeliveryMan $monLivreur;
    private DeliveryMan $sonLivreur;
    private Hub $monEntrepot;
    private Hub $sonEntrepot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monColis = $this->colisDe($this->marchandDe(settings()->id));

        $this->monLivreur = $this->livreurDe(settings()->id, 'A');
        $this->sonLivreur = $this->livreurDe(self::AUTRE, 'B');

        $this->monEntrepot = Hub::forceCreate([
            'company_id' => settings()->id, 'name' => 'Entrepot maison S45', 'status' => 1,
        ]);
        $this->sonEntrepot = Hub::forceCreate([
            'company_id' => self::AUTRE, 'name' => 'Entrepot voisin S45', 'status' => 1,
        ]);

        $this->actingAs($this->agentDe(settings()->id));

        $this->depot = app(ParcelInterface::class);
    }

    /* ─────────────── l'axe du livreur : une ecriture qui franchit ────────── */

    /**
     * Le chemin le plus cher du lot. Le solde du livreur nomme est credite du
     * frais de retour, et deux releves portant NOTRE `company_id` sont attaches
     * a son identifiant. Nommer le livreur d'une autre societe faisait payer
     * son employe par nos livres.
     */
    public function test_return_to_merchant_refuses_a_deliveryman_of_another_company(): void
    {
        $soldeAvant = (float) $this->sonLivreur->current_balance;

        $this->assertFalse($this->depot->returnAssignToMerchant($this->monColis->id, new Request([
            'delivery_man_id' => $this->sonLivreur->id,
            'date' => date('Y-m-d'),
        ])));

        $this->assertSame($soldeAvant, (float) $this->sonLivreur->fresh()->current_balance, 'le solde d\'un livreur etranger a bouge');
        $this->assertSame(0, DeliverymanStatement::where('delivery_man_id', $this->sonLivreur->id)->count(), 'un releve de paie a ete attache a un livreur etranger');
        $this->assertSame(0, CourierStatement::where('delivery_man_id', $this->sonLivreur->id)->count(), 'un releve transporteur a ete attache a un livreur etranger');
        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->monColis->id)->count());

        // Controle negatif : notre livreur, lui, est bien paye.
        $this->assertTrue($this->depot->returnAssignToMerchant($this->monColis->id, new Request([
            'delivery_man_id' => $this->monLivreur->id,
            'date' => date('Y-m-d'),
        ])));
        $this->assertSame(ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, $this->monColis->fresh()->status);
        $this->assertSame(1, DeliverymanStatement::where('delivery_man_id', $this->monLivreur->id)->count());
    }

    /** Le meme geste en lot : une fois par colis, donc le defaut s'y multipliait. */
    public function test_bulk_return_to_merchant_refuses_a_deliveryman_of_another_company(): void
    {
        $soldeAvant = (float) $this->sonLivreur->current_balance;

        $this->assertFalse($this->depot->AssignReturnToMerchantBulk(new Request([
            'parcel_id' => [$this->monColis->id],
            'delivery_man_id' => $this->sonLivreur->id,
            'date' => date('Y-m-d'),
        ])));

        $this->assertSame($soldeAvant, (float) $this->sonLivreur->fresh()->current_balance);
        $this->assertSame(0, DeliverymanStatement::where('delivery_man_id', $this->sonLivreur->id)->count());
        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);

        // Controle negatif.
        $this->assertTrue($this->depot->AssignReturnToMerchantBulk(new Request([
            'parcel_id' => [$this->monColis->id],
            'delivery_man_id' => $this->monLivreur->id,
            'date' => date('Y-m-d'),
        ])));
        $this->assertSame(ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, $this->monColis->fresh()->status);
    }

    /** La reprogrammation du retour : meme identifiant, meme angle mort. */
    public function test_return_reschedule_refuses_a_deliveryman_of_another_company(): void
    {
        $this->assertFalse($this->depot->returnAssignToMerchantReschedule($this->monColis->id, new Request([
            'delivery_man_id' => $this->sonLivreur->id,
            'date' => date('Y-m-d'),
        ])));

        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->monColis->id)->count());

        $this->assertTrue($this->depot->returnAssignToMerchantReschedule($this->monColis->id, new Request([
            'delivery_man_id' => $this->monLivreur->id,
            'date' => date('Y-m-d'),
        ])));
        $this->assertSame(ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE, $this->monColis->fresh()->status);
    }

    /** Affectation en lot : le livreur vaut pour tout le lot, donc le lot entier tombe. */
    public function test_bulk_deliveryman_assignment_refuses_a_deliveryman_of_another_company(): void
    {
        $this->assertFalse($this->depot->deliveryManAssignMultipleParcel(new Request([
            'parcel_ids_' => [$this->monColis->id],
            'delivery_man_id' => $this->sonLivreur->id,
        ])));

        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->monColis->id)->count());

        $this->assertTrue($this->depot->deliveryManAssignMultipleParcel(new Request([
            'parcel_ids_' => [$this->monColis->id],
            'delivery_man_id' => $this->monLivreur->id,
        ])));
        $this->assertSame(ParcelStatus::DELIVERY_MAN_ASSIGN, $this->monColis->fresh()->status);
    }

    /** Meme symetrie cote ramassage. */
    public function test_bulk_pickup_assignment_refuses_a_pickupman_of_another_company(): void
    {
        $this->assertFalse($this->depot->pickupdatemanAssignedBulk(new Request([
            'parcel_id' => [$this->monColis->id],
            'delivery_man_id' => $this->sonLivreur->id,
        ])));

        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->monColis->id)->count());

        $this->assertTrue($this->depot->pickupdatemanAssignedBulk(new Request([
            'parcel_id' => [$this->monColis->id],
            'delivery_man_id' => $this->monLivreur->id,
        ])));
        $this->assertSame(ParcelStatus::PICKUP_ASSIGN, $this->monColis->fresh()->status);
    }

    /* ──────────── l'axe de l'entrepot : notre colis qui disparait ────────── */

    /**
     * Un `hub_id` etranger ne montre RIEN a l'autre societe : ses listes
     * d'entrepot restent `companywise()`. Il fait autre chose, et c'est pour
     * nous : le colis quitte toutes NOS listes d'entrepot — celles-ci croisent
     * `companywise()` ET `hub_id` — et devient introuvable a nos agents.
     */
    public function test_warehouse_reception_refuses_a_hub_of_another_company(): void
    {
        // La reception en entrepot paie le ramasseur : sans evenement de
        // ramassage prealable elle echoue de toute facon. Mesurer le refus dans
        // cet etat-la ne prouverait RIEN — le faux serait deja acquis sans la
        // garde. On place donc le colis dans l'etat ou l'appel REUSSIRAIT.
        $this->assertTrue($this->depot->pickupdatemanAssignedBulk(new Request([
            'parcel_id' => [$this->monColis->id],
            'delivery_man_id' => $this->monLivreur->id,
        ])));

        $this->assertFalse($this->depot->receivedWarehouse($this->monColis->id, new Request([
            'hub_id' => $this->sonEntrepot->id,
        ])));

        $this->assertNull($this->monColis->fresh()->hub_id, 'notre colis a ete range dans l\'entrepot d\'une autre societe');
        $this->assertSame(ParcelStatus::PICKUP_ASSIGN, $this->monColis->fresh()->status);

        $this->assertTrue($this->depot->receivedWarehouse($this->monColis->id, new Request([
            'hub_id' => $this->monEntrepot->id,
        ])));
        $this->assertSame($this->monEntrepot->id, $this->monColis->fresh()->hub_id);
    }

    /** Le transfert unitaire : meme identifiant, meme consequence. */
    public function test_transfer_to_hub_refuses_a_hub_of_another_company(): void
    {
        $this->assertFalse($this->depot->transfertohub($this->monColis->id, new Request([
            'hub_id' => $this->sonEntrepot->id,
        ])));

        $this->assertNull($this->monColis->fresh()->transfer_hub_id);
        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);

        $this->assertTrue($this->depot->transfertohub($this->monColis->id, new Request([
            'hub_id' => $this->monEntrepot->id,
        ])));
        $this->assertSame($this->monEntrepot->id, $this->monColis->fresh()->transfer_hub_id);
    }

    /**
     * Le transfert porte DEUX identifiants secondaires, et le livreur y est
     * facultatif : le controleur n'exige que `hub_id`. La garde ne doit donc
     * pas refuser un transfert sans livreur — mais doit refuser un livreur
     * etranger quand il est nomme.
     */
    public function test_transfer_to_hub_refuses_a_named_foreign_deliveryman_but_tolerates_none(): void
    {
        $this->assertFalse($this->depot->transfertohub($this->monColis->id, new Request([
            'hub_id' => $this->monEntrepot->id,
            'delivery_man_id' => $this->sonLivreur->id,
        ])));
        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);

        // Controle negatif nº1 : aucun livreur nomme — le transfert passe.
        $this->assertTrue($this->depot->transfertohub($this->monColis->id, new Request([
            'hub_id' => $this->monEntrepot->id,
        ])));

        // Controle negatif nº2 : notre livreur nomme — il passe, et il est inscrit.
        $this->assertTrue($this->depot->transfertohub($this->monColis->id, new Request([
            'hub_id' => $this->monEntrepot->id,
            'delivery_man_id' => $this->monLivreur->id,
        ])));
        $this->assertSame(
            $this->monLivreur->id,
            ParcelEvent::where('parcel_id', $this->monColis->id)->latest('id')->first()->transfer_delivery_man_id,
        );
    }

    /** Le meme transfert en lot. */
    public function test_bulk_transfer_to_hub_refuses_a_hub_of_another_company(): void
    {
        $this->assertFalse($this->depot->transferToHubMultipleParcel(new Request([
            'parcel_ids' => [$this->monColis->id],
            'hub_id' => $this->sonEntrepot->id,
        ])));

        $this->assertNull($this->monColis->fresh()->transfer_hub_id);
        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);

        $this->assertTrue($this->depot->transferToHubMultipleParcel(new Request([
            'parcel_ids' => [$this->monColis->id],
            'hub_id' => $this->monEntrepot->id,
        ])));
        $this->assertSame($this->monEntrepot->id, $this->monColis->fresh()->transfer_hub_id);
    }

    /**
     * Le lot porte lui aussi les DEUX identifiants secondaires, et le sabotage
     * l'a rappele : la garde de l'entrepot etait prouvee, celle du livreur ne
     * l'etait pas — ce test-ci manquait, et son absence rendait la garde verte
     * sans rien etablir.
     */
    public function test_bulk_transfer_to_hub_refuses_a_named_foreign_deliveryman(): void
    {
        $this->assertFalse($this->depot->transferToHubMultipleParcel(new Request([
            'parcel_ids' => [$this->monColis->id],
            'hub_id' => $this->monEntrepot->id,
            'delivery_man_id' => $this->sonLivreur->id,
        ])));

        $this->assertSame(ParcelStatus::PENDING, $this->monColis->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $this->monColis->id)->count());

        // Controle negatif nº1 : aucun livreur nomme — le lot passe.
        $this->assertTrue($this->depot->transferToHubMultipleParcel(new Request([
            'parcel_ids' => [$this->monColis->id],
            'hub_id' => $this->monEntrepot->id,
        ])));

        // Controle negatif nº2 : notre livreur — il passe, et il est inscrit.
        $this->assertTrue($this->depot->transferToHubMultipleParcel(new Request([
            'parcel_ids' => [$this->monColis->id],
            'hub_id' => $this->monEntrepot->id,
            'delivery_man_id' => $this->monLivreur->id,
        ])));
        $this->assertSame(
            $this->monLivreur->id,
            ParcelEvent::where('parcel_id', $this->monColis->id)->latest('id')->first()->transfer_delivery_man_id,
        );
    }

    /* ───────────────── l'axe du colis, la ou il manquait ─────────────────── */

    /**
     * La reprogrammation du retour est la seule etape unitaire dont l'axe du
     * COLIS n'etait etabli nulle part : `ParcelLifecycleTest` enumere neuf
     * etapes et s'arrete avant elle. La garde existait ; rien ne la tenait.
     */
    public function test_return_reschedule_refuses_a_parcel_of_another_company(): void
    {
        $colisAilleurs = $this->colisDe($this->marchandDe(self::AUTRE));

        $this->assertFalse($this->depot->returnAssignToMerchantReschedule($colisAilleurs->id, new Request([
            'delivery_man_id' => $this->monLivreur->id,
            'date' => date('Y-m-d'),
        ])));

        $this->assertSame(ParcelStatus::PENDING, $colisAilleurs->fresh()->status);
        $this->assertSame(0, ParcelEvent::where('parcel_id', $colisAilleurs->id)->count());

        // Controle negatif : le notre, dans la meme requete, passe bien.
        $this->assertTrue($this->depot->returnAssignToMerchantReschedule($this->monColis->id, new Request([
            'delivery_man_id' => $this->monLivreur->id,
            'date' => date('Y-m-d'),
        ])));
    }

    /* ───────────────────────────── fixtures ─────────────────────────────── */

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent agent-scope';
        $agent->email = 'agent.s45.' . $societe . '@example.test';
        $agent->mobile = '00229971100' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand agent-scope ' . $societe;
        $utilisateur->email = 'marchand.s45.' . $societe . '@example.test';
        $utilisateur->mobile = '00229971200' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME agent-scope ' . $societe,
            'current_balance' => 0,
        ]);
    }

    private function livreurDe(int $societe, string $marque): DeliveryMan
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Livreur agent-scope ' . $marque;
        $utilisateur->email = 'livreur.s45.' . $marque . '@example.test';
        $utilisateur->mobile = '0022997130' . $marque . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::DELIVERYMAN;
        $utilisateur->unique_id = 'L-S45-' . $marque;
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
            'tracking_id' => 'BL-S45-' . $marchand->company_id . '-' . uniqid(),
            'customer_name' => 'Client de la societe ' . $marchand->company_id,
            'customer_phone' => '0022997123456',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => ParcelStatus::PENDING,
        ]);
    }
}
