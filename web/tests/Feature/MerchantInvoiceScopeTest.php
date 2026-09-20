<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\MerchantInvoiceController;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\User;
use App\Repositories\Invoice\InvoiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S32 — les relevés de règlement, septième passe sur l'arriéré du filet.
 *
 * Cette passe est une **vérification**, pas une correction, et c'est le résultat
 * qui compte : les six méthodes de `InvoiceRepository` que ces neuf routes
 * atteignent sont **déjà** `companywise()`, posées par S14, S20 et le chantier 4.
 * L'arriéré les tenait faute de test, pas faute de périmètre.
 *
 * Trois couches de périmètre se superposent ici, et il vaut la peine de les
 * nommer parce qu'elles ne protègent pas des mêmes choses :
 *
 * 1. **`companywise()` dans le dépôt** — protège d'un administrateur d'une autre
 *    société. C'est la couche qui manquait partout ailleurs dans l'arriéré.
 * 2. **`ownsOrAbort()` dans le contrôleur** (S20) — protège d'un **marchand** qui
 *    forgerait le `merchant_id` de l'URL depuis son propre panneau. Elle ne
 *    s'applique qu'aux comptes marchands : pour un administrateur, c'est la
 *    couche 1 qui travaille.
 * 3. **Le lien signé** de `signedPdf()` — déjà déclaré *publique à dessein* dans
 *    le filet : la signature EST l'authentification, et c'est ainsi que l'app
 *    mobile ouvre le PDF, sans pouvoir joindre son jeton à un navigateur.
 *
 * Un seul défaut trouvé, de la famille connue : `InvoiceDetails()` déréférençait
 * un `null` hors périmètre — **500** au lieu de 404.
 *
 * ⚠️ **Un piège à signaler pour qui touchera ce dépôt.**
 * `InvoiceRepository::InvoicePdf($merchant_id, $invoice_id)` et
 * `InvoiceRepository::invoiceGet($merchant_id, $invoice_id)` ont un corps
 * **identique, ligne pour ligne**. Seule `invoiceGet()` est appelée ; `InvoicePdf()`
 * est du code mort (déclaré dans l'interface, appelé par personne — l'action
 * `InvoicePdf` du contrôleur passe par `invoiceGet`).
 *
 * Ce doublon a avalé un de mes sabotages : le remplacement a touché le jumeau mort
 * et le test est resté vert alors que le fichier avait bien changé. Un correctif
 * appliqué au mauvais jumeau serait tout aussi silencieux. Le doublon est laissé en
 * place — il est scopé de la même façon, et retirer une méthode d'interface dépasse
 * le cadre d'une passe d'isolation — mais il est **signalé**.
 */
class MerchantInvoiceScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    private Merchant $monMarchand;
    private Merchant $sonMarchand;
    private Invoice $monReleve;
    private Invoice $sonReleve;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $this->monMarchand = $this->marchandDe(settings()->id);
        $this->sonMarchand = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $this->monReleve = $this->releveDe($this->monMarchand);
        $this->sonReleve = $this->releveDe($this->sonMarchand);

        $this->actingAs($this->agentDe(settings()->id));
    }

    /* ───────── couche 1 : le dépôt, contre un administrateur d'ailleurs ───── */

    public function test_no_repository_read_reaches_the_statement_of_another_company(): void
    {
        $depot = app(InvoiceInterface::class);
        $ouvertes = [];

        $lectures = [
            'merchantInvoiceGet' => fn () => $depot->merchantInvoiceGet($this->sonMarchand->id)->count() > 0,
            'merchantInvoiceDetails' => fn () => filled($depot->merchantInvoiceDetails($this->sonMarchand->id, $this->sonReleve->invoice_id)),
            'invoiceGet' => fn () => filled($depot->invoiceGet($this->sonMarchand->id, $this->sonReleve->invoice_id)),
        ];

        foreach ($lectures as $nom => $appel) {
            if ($appel()) {
                $ouvertes[] = $nom;
            }
        }

        $this->assertSame([], $ouvertes, "Relevés d'une autre société lisibles :\n - " . implode("\n - ", $ouvertes));
    }

    /**
     * Le changement de statut : marquer « payé » le relevé d'une autre société,
     * c'est solder sa dette envers son marchand sans que rien n'ait été versé.
     */
    public function test_the_status_of_another_companys_statement_cannot_be_changed(): void
    {
        $statutDorigine = (int) $this->sonReleve->fresh()->status;

        $requete = new Request([
            'id' => $this->sonReleve->id,
            'invoice_id' => $this->sonReleve->invoice_id,
            'status' => \App\Enums\InvoiceStatus::PAID,
        ]);

        $this->assertFalse((bool) app(InvoiceInterface::class)->statusUpdate($requete, $this->sonMarchand->id));
        $this->assertSame($statutDorigine, (int) $this->sonReleve->fresh()->status);
    }

    /** Et les six écrans refusent, chacun à sa manière. */
    public function test_the_statement_screens_refuse_another_companys_merchant(): void
    {
        $controleur = app(MerchantInvoiceController::class);
        $ouverts = [];

        $appels = [
            'details' => fn () => $controleur->InvoiceDetails($this->sonMarchand->id, $this->sonReleve->invoice_id),
            'pdf' => fn () => $controleur->InvoicePdf($this->sonMarchand->id, $this->sonReleve->invoice_id),
            'journal' => fn () => $controleur->InvoiceJournal($this->sonMarchand->id, $this->sonReleve->invoice_id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $ouverts[] = $nom;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $nom);
            }
        }

        $this->assertSame([], $ouverts, "Écrans de relevé ouverts sur le marchand d'une autre société :\n - "
            . implode("\n - ", $ouverts));
    }

    /**
     * ⚠️ `index()` est un cas à part, et je le laisse tel quel : la liste est
     * `companywise()`, donc pour le marchand d'une autre société elle rend une
     * liste **vide** — pas un 404. Rien ne fuit, et je ne change pas un
     * comportement pour la seule symétrie. Le test l'inscrit tel qu'il est.
     */
    public function test_the_statement_list_of_another_companys_merchant_is_empty_not_a_leak(): void
    {
        $vue = app(MerchantInvoiceController::class)->index($this->sonMarchand->id);

        $this->assertEmpty($vue->getData()['invoices'], 'la liste des relevés d\'un marchand d\'ailleurs n\'est pas vide');
    }

    /* ───────── couche 2 : `ownsOrAbort`, contre un marchand qui forge ─────── */

    /**
     * Depuis le panneau marchand, les trois routes de téléchargement portent
     * `merchant_id` dans l'URL. C'est le défaut S20, et son garde est ici exercé
     * pour la première fois par un test.
     */
    public function test_a_merchant_cannot_download_another_merchants_statement(): void
    {
        $voisin = $this->marchandDe(settings()->id, 'voisin');
        $sonReleveAMoiLaSociete = $this->releveDe($voisin);

        // Un marchand connecté, de MA société — donc `companywise()` ne l'arrête
        // pas : c'est `ownsOrAbort()` qui doit refuser. Le cas que la décision S7
        // nomme comme le plus fréquent.
        $this->actingAs($this->marchandUtilisateur($this->monMarchand));

        $controleur = app(MerchantInvoiceController::class);
        $ouverts = [];

        foreach (['InvoicePdf', 'InvoiceJournal', 'InvoiceCSV'] as $methode) {
            try {
                $controleur->{$methode}($voisin->id, $sonReleveAMoiLaSociete->invoice_id);
                $ouverts[] = $methode;
            } catch (NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode(), $methode);
            }
        }

        $this->assertSame([], $ouverts, "Un marchand a téléchargé le relevé d'un autre marchand :\n - "
            . implode("\n - ", $ouverts));
    }

    /* ─────────────────── les contrôles négatifs ─────────────────────────── */

    public function test_my_own_statements_are_still_reachable(): void
    {
        $depot = app(InvoiceInterface::class);

        $this->assertGreaterThan(0, $depot->merchantInvoiceGet($this->monMarchand->id)->count());
        $this->assertNotNull($depot->merchantInvoiceDetails($this->monMarchand->id, $this->monReleve->invoice_id));
        $this->assertNotNull($depot->invoiceGet($this->monMarchand->id, $this->monReleve->invoice_id));

        $this->assertTrue((bool) $depot->statusUpdate(new Request([
            'id' => $this->monReleve->id,
            'invoice_id' => $this->monReleve->invoice_id,
            'status' => \App\Enums\InvoiceStatus::PAID,
        ]), $this->monMarchand->id));

        $this->assertSame(\App\Enums\InvoiceStatus::PAID, (int) $this->monReleve->fresh()->status);
    }

    /** Et le marchand télécharge bien SON propre relevé. */
    public function test_a_merchant_still_downloads_his_own_statement(): void
    {
        $this->actingAs($this->marchandUtilisateur($this->monMarchand));

        $reponse = app(MerchantInvoiceController::class)->InvoicePdf($this->monMarchand->id, $this->monReleve->invoice_id);

        $this->assertSame(200, $reponse->getStatusCode());
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent releve ' . $societe;
        $agent->email = 'agent.releve.' . $societe . '@example.test';
        $agent->mobile = '00229970071' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe, string $suffixe = 'principal'): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand ' . $suffixe . ' ' . $societe;
        $utilisateur->email = 'marchand.' . $suffixe . '.' . $societe . '@example.test';
        $utilisateur->mobile = '00229970072' . $societe . strlen($suffixe);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME ' . $suffixe . ' ' . $societe,
            'current_balance' => 0,
        ]);
    }

    private function marchandUtilisateur(Merchant $marchand): User
    {
        return User::findOrFail($marchand->user_id);
    }

    private function releveDe(Merchant $marchand): Invoice
    {
        return Invoice::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'invoice_id' => 'FACT-' . $marchand->company_id . '-' . $marchand->id,
            'invoice_date' => now()->toDateString(),
            'issued_on' => now()->toDateString(),
            'total_charge' => 15000,
            'cash_collection' => 100000,
            'current_payable' => 85000,
            'status' => \App\Enums\InvoiceStatus::UNPAID,
        ]);
    }
}
