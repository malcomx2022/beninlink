<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Http\Controllers\Backend\ExpenseController;
use App\Http\Controllers\Backend\IncomeController;
use App\Mail\InvoicePDFSend;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Repositories\Invoice\InvoiceInterface;
use App\Repositories\Invoice\InvoiceRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as Router;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S69 — le lot de nettoyage T1 (`docs/CARTOGRAPHIE_PROJET.md`).
 *
 * Quatre morceaux de code **mort** hérités du socle, relevés au fil des chantiers
 * et jamais touchés parce qu'« aucun comportement n'était à corriger » :
 *
 *  1. `App\Mail\InvoicePDFSend` — jamais instancié ; expéditeur `admin@example.com`,
 *     sujet anglais, et un nom de vue qui ne résolvait **pas sous Linux**
 *     (`invoice_mail_pdf` pour un fichier `Invoice_mail_pdf.blade.php`). Il
 *     fonctionnait sur le poste de l'éditeur (casse insensible) et aurait planté
 *     en production au premier branchement (charte-web §11.6 b).
 *  2. `backend/merchant/invoice/invoice_pdf.blade.php` — rendue nulle part, elle
 *     référençait deux constantes **inexistantes** de `ParcelStatus` : pour un colis
 *     livré, PHP lève `Undefined constant` (charte-web §11.6 a).
 *  3. `InvoiceRepository::InvoicePdf()` — doublon mort, corps pour corps, de
 *     `invoiceGet()`, qui a **avalé un sabotage** en S32 : un correctif appliqué au
 *     mauvais jumeau aurait été tout aussi silencieux.
 *  4. `IncomeController::searchAccount()` — sa route a disparu, l'écran des revenus
 *     appelle celle des dépenses, et elle passait l'objet `Request` là où un
 *     identifiant est attendu (S35, « ce qui n'est pas fermé »).
 *
 * Règle du projet : **0 fichier supprimé du socle** (`docs/guides/socle/`). Les
 * deux fichiers sont donc conservés et rendus **justes** — le mailable suit D13 et
 * F4 comme `MerchantFeedMail`, la vue orpheline délègue au relevé officiel du
 * chantier 4. Les deux méthodes, elles, sont retirées : une méthode n'est pas un
 * fichier, et le dépôt a déjà retiré une route (S28).
 *
 * Le dernier test généralise la ligne « Routes mortes repérées » de
 * `web/CARTOGRAPHIE.md` : toute route déclarée vise une méthode de contrôleur qui
 * **existe**. Le bloc G y avait trouvé deux routes PDF sur une méthode inexistante,
 * à la main ; la suite le lit désormais à chaque commit.
 */
class LegacyDeadCodeCleanupTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    private Merchant $marchand;
    private Invoice $releve;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        // Un marchand d'une AUTRE société que l'ambiante : c'est ce qui rend F4
        // mesurable (voir `MerchantFeedMailTest`).
        $this->marchand = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $this->releve = Invoice::forceCreate([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'invoice_id' => 'FACT-S69-' . $this->marchand->id,
            'invoice_date' => now()->toDateString(),
            'issued_on' => now()->toDateString(),
            'total_charge' => 15000,
            'cash_collection' => 100000,
            'current_payable' => 85000,
            'status' => InvoiceStatus::UNPAID,
        ]);
    }

    /* ─────────────── 1. le mailable : une vue qui résout, une marque, une file ── */

    public function test_the_statement_mail_resolves_its_view_on_a_case_sensitive_filesystem(): void
    {
        // Le défaut d'origine : `invoice_mail_pdf` n'existe pas, `Invoice_mail_pdf` si.
        $this->assertFalse(view()->exists('backend.merchant.invoice.invoice_mail_pdf'),
            'si cette vue existe désormais, le fichier a été renommé : adapter le mailable et ce test');
        $this->assertTrue(view()->exists('backend.merchant.invoice.Invoice_mail_pdf'));

        // Le code, sans ses commentaires : le docbloc RACONTE le défaut d'origine
        // (`admin@example.com`, `settings()`), ce n'est pas lui qu'on mesure.
        $source = $this->codeSansCommentaires(app_path('Mail/InvoicePDFSend.php'));
        $this->assertStringContainsString("'backend.merchant.invoice.Invoice_mail_pdf'", $source,
            'le mailable doit nommer la vue avec la casse du fichier');
        $this->assertStringNotContainsString('example.com', $source, 'plus d’expéditeur codé en dur');
        $this->assertStringNotContainsString('settings()', $source, 'F4 — la marque est passée, jamais lue');
    }

    public function test_the_statement_mail_carries_the_recipient_brand_and_the_official_pdf(): void
    {
        $ambiante = GeneralSettings::orderBy('id')->firstOrFail();
        $sienne = GeneralSettings::findOrFail($this->marchand->company_id);
        $this->assertNotSame((int) $ambiante->id, (int) $sienne->id);
        $this->assertNotSame($ambiante->email, $sienne->email,
            'les deux sociétés doivent être distinguables par leur courriel de contact');

        $mail = new InvoicePDFSend($this->releve, $this->marqueDe($sienne), 'Kola Distribution');
        $rendu = $mail->render();

        // Sujet français, numéroté.
        $this->assertStringContainsString(__('statement.title'), $mail->subject);
        $this->assertStringContainsString($this->releve->invoice_id, $mail->subject);

        // F4 : l'expéditeur et le corps portent SA société, pas l'ambiante.
        $this->assertSame($sienne->email, $mail->from[0]['address']);
        $this->assertStringContainsString($sienne->email, $rendu);
        $this->assertStringNotContainsString($ambiante->email, $rendu);
        $this->assertStringContainsString('Kola Distribution', $rendu);
        $this->assertStringContainsString($this->releve->invoice_id, $rendu);

        // Un seul document : le PDF joint est le relevé du chantier 4.
        $this->assertCount(1, $mail->rawAttachments);
        $this->assertSame('releve-' . $this->releve->invoice_id . '.pdf', $mail->rawAttachments[0]['name']);
        $this->assertSame('application/pdf', $mail->rawAttachments[0]['options']['mime']);
        $this->assertStringStartsWith('%PDF', $mail->rawAttachments[0]['data']);
    }

    public function test_the_statement_mail_is_queued_not_sent_in_the_request(): void
    {
        $this->assertTrue(
            (new \ReflectionClass(InvoicePDFSend::class))->implementsInterface(ShouldQueue::class),
            'D13 — un envoi sortant (et le rendu d’un PDF) ne se fait pas dans la requête',
        );
    }

    /* ─────────────── 2. la vue orpheline : plus de constante fantôme ─────────── */

    public function test_the_orphan_pdf_view_renders_the_official_statement_instead_of_a_fatal_error(): void
    {
        // La prémisse du défaut : ces deux constantes n'existent toujours pas.
        foreach (['RETURN_TRANSFER_BY_HUB', 'RETURN_RECEIVED_PARCEL'] as $constante) {
            $this->assertFalse(defined(ParcelStatus::class . '::' . $constante),
                "si `$constante` existe désormais, la vue d’origine n’était plus fautive : relire ce lot");
        }

        $source = preg_replace('/\{\{--.*?--\}\}/s', '', // le commentaire Blade raconte le défaut
            file_get_contents(resource_path('views/backend/merchant/invoice/invoice_pdf.blade.php')));
        $this->assertStringNotContainsString('ParcelStatus::', $source,
            'la vue ne doit plus porter sa propre table des statuts (quatrième copie, charte-web §11.1)');
        $this->assertStringContainsString("@include('backend.invoice.statement_pdf'", $source,
            'la vue orpheline délègue au relevé officiel — un seul document');

        $rendu = view('backend.merchant.invoice.invoice_pdf', ['invoice' => $this->releve])->render();

        $this->assertStringContainsString(__('statement.title'), $rendu);
        $this->assertStringContainsString($this->releve->invoice_id, $rendu);
        $this->assertStringNotContainsString('Terms and Conditions', $rendu);
        $this->assertStringNotContainsString('(TK)', $rendu, 'plus de taka bangladais');
    }

    /* ─────────────── 3. le dépôt : un seul point de lecture ─────────────────── */

    public function test_the_invoice_repository_has_a_single_lookup_method(): void
    {
        $this->assertFalse(method_exists(InvoiceRepository::class, 'InvoicePdf'),
            'le doublon mort est revenu : il avalerait de nouveau un correctif appliqué au mauvais jumeau');
        $this->assertFalse((new \ReflectionClass(InvoiceInterface::class))->hasMethod('InvoicePdf'));
        $this->assertTrue(method_exists(InvoiceRepository::class, 'invoiceGet'));
    }

    /* ─────────────── 4. le contrôleur : la méthode sans route ────────────────── */

    public function test_the_income_controller_no_longer_carries_the_account_lookup_without_a_route(): void
    {
        $this->assertFalse(method_exists(IncomeController::class, 'searchAccount'));

        // L'écran des revenus n'a rien perdu : il appelait déjà la route des dépenses.
        $js = file_get_contents(public_path('backend/js/income/custom.js'));
        $this->assertStringContainsString('/admin/expense/search-account/', $js);

        $this->mountTenantRoutes();
        $route = Router::getRoutes()->getByName('expense.search-account');
        $this->assertNotNull($route);
        $this->assertSame(ExpenseController::class . '@searchAccount', $route->getActionName());
    }

    /* ─────────────── 5. le filet : aucune route ne vise le vide ─────────────── */

    /**
     * Le bloc G de `web/CARTOGRAPHIE.md` avait trouvé **à la main** deux routes PDF
     * déclarées sur une méthode inexistante (`route:list` ne le dit pas). Ici, la
     * suite le lit : chaque route à contrôleur vise une classe et une méthode qui
     * existent. Les routes du locataire ne sont montées qu'avec un domaine — d'où
     * `mountTenantRoutes()`, sans quoi ce test ne lirait que l'API et le super-admin.
     */
    public function test_every_declared_route_targets_an_existing_controller_method(): void
    {
        $this->mountTenantRoutes();

        $routes = Router::getRoutes()->getRoutes();
        $this->assertGreaterThan(300, count($routes), 'les routes du locataire doivent être montées');

        $mortes = [];
        foreach ($routes as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure') {
                continue;
            }
            [$classe, $methode] = array_pad(explode('@', $action, 2), 2, '__invoke');
            if (!class_exists($classe) || !method_exists($classe, $methode)) {
                $mortes[] = implode('|', $route->methods()) . ' ' . $route->uri() . ' → ' . $action;
            }
        }

        $this->assertSame([], $mortes,
            "Routes déclarées sur une méthode de contrôleur inexistante (404/500 garanti) :\n - "
            . implode("\n - ", $mortes));
    }

    /** Le source PHP d'un fichier, commentaires et docblocs retirés. */
    private function codeSansCommentaires(string $chemin): string
    {
        $code = '';
        foreach (token_get_all(file_get_contents($chemin)) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $code .= $token[1];
            } else {
                $code .= $token;
            }
        }

        return $code;
    }

    /** Les mêmes cinq lectures qu'`EmailChannel::marqueDe()`, sur la société du destinataire. */
    private function marqueDe(GeneralSettings $societe): array
    {
        return [
            'nom' => $societe->name,
            'logo' => $societe->rxlogo?->original,
            'courriel' => $societe->email,
            'telephone' => $societe->phone,
            'mentions' => $societe->copyright,
        ];
    }
}
