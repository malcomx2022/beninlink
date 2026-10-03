<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Mail\InvoicePDFSend;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Repositories\Invoice\InvoiceInterface;
use App\Services\Invoicing\SettlementPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **R9 (S75)** — le relevé de règlement part au marchand par courriel, à l'émission.
 *
 * Le mailable était prêt depuis S69 et volontairement non branché. Le porteur
 * a tranché : envoi **en file** à l'émission du relevé, au courriel du compte
 * marchand, avec **le** PDF officiel — identique au téléchargement. Et la règle
 * **D8** : jamais de réémission modifiée. Ce fichier tient les trois critères.
 */
class StatementEmailTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->marchand = Merchant::firstOrFail();
        $this->marchand->payment_period = 0;
        $this->marchand->save();
        $this->actingAs($this->marchand->user->fresh());
    }

    private function colisLivre(string $suivi = 'BL-MAIL'): void
    {
        $colis = new Parcel();
        $colis->forceFill([
            'company_id' => $this->marchand->company_id, 'merchant_id' => $this->marchand->id,
            'customer_name' => 'Client', 'customer_address' => 'Cotonou', 'category_id' => 1, 'delivery_type_id' => 1,
            'cash_collection' => 20000, 'delivery_charge' => 1000, 'cod_amount' => 0, 'vat' => 18, 'vat_amount' => 180,
            'total_delivery_amount' => 1000, 'current_payable' => 18820,
            'tracking_id' => $suivi, 'status' => ParcelStatus::DELIVERED,
        ])->save();
    }

    public function test_l_emission_d_un_releve_met_le_courriel_en_file_pour_le_compte_marchand(): void
    {
        Mail::fake();
        $this->colisLivre();

        $releve = app(InvoiceInterface::class)->store($this->marchand->id);
        $this->assertInstanceOf(Invoice::class, $releve);

        $courriel = $this->marchand->user->email;
        Mail::assertQueued(InvoicePDFSend::class, function (InvoicePDFSend $mail) use ($releve, $courriel) {
            return $mail->invoice->id === $releve->id
                && $mail->destinataire === $courriel
                && $mail->hasTo($courriel);
        });
        Mail::assertQueued(InvoicePDFSend::class, 1);
    }

    /** F4 : la marque est celle de la société du relevé, lue par identifiant — y compris hors requête. */
    public function test_le_passage_planifie_envoie_aussi_avec_la_marque_de_la_societe_du_releve(): void
    {
        Mail::fake();
        auth()->logout();
        $this->colisLivre('BL-PLANIFIE');

        $this->artisan('invoice:generate', ['--societe' => $this->marchand->company_id])->assertSuccessful();

        $nom = \App\Models\Backend\GeneralSettings::find($this->marchand->company_id)->name;
        Mail::assertQueued(InvoicePDFSend::class, fn (InvoicePDFSend $mail) => $mail->marque['nom'] === $nom);
    }

    public function test_un_compte_marchand_sans_courriel_recoit_son_releve_quand_meme_sans_envoi(): void
    {
        Mail::fake();
        $user = $this->marchand->user;
        $user->email = '';
        $user->save();
        $this->colisLivre();

        $releve = app(InvoiceInterface::class)->store($this->marchand->id);

        $this->assertInstanceOf(Invoice::class, $releve, 'le relevé est émis : le courriel n’est pas une condition');
        Mail::assertNothingQueued();
    }

    public function test_la_piece_jointe_est_le_pdf_officiel_sous_le_meme_nom(): void
    {
        $this->colisLivre();
        Mail::fake();
        $releve = app(InvoiceInterface::class)->store($this->marchand->id)->fresh();

        $mail = new InvoicePDFSend($releve, ['nom' => 'Transporteur', 'logo' => null, 'courriel' => 'contact@example.test', 'telephone' => '', 'mentions' => ''], 'pme@example.test');
        $mail->build();

        $this->assertCount(1, $mail->rawAttachments);
        $piece = $mail->rawAttachments[0];
        $this->assertSame(SettlementPdf::fileName($releve), $piece['name']);
        $this->assertSame('releve-' . $releve->invoice_id . '.pdf', $piece['name']);
        $this->assertStringStartsWith('%PDF', $piece['data']);
        // Le téléchargement produit le même document, par le même point de rendu.
        $this->assertSame(strlen(SettlementPdf::render($releve)), strlen($piece['data']));
    }

    /** « Identique au téléchargement » tient parce qu'il n'existe qu'UN point de rendu. */
    public function test_le_pdf_du_releve_n_a_qu_un_seul_point_de_rendu(): void
    {
        $fichiers = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path())) as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.php') && str_contains(file_get_contents($f->getPathname()), "'backend.invoice.statement_pdf'")) {
                $fichiers[] = str_replace(app_path() . '/', '', $f->getPathname());
            }
        }
        sort($fichiers);

        $this->assertSame(['Services/Invoicing/SettlementPdf.php'], $fichiers,
            'la vue du relevé est rendue ailleurs que dans SettlementPdf : le courriel et le téléchargement pourraient diverger');
    }

    /** D8 : aucune route n'écrit sur un relevé émis — il n'y a pas de « réémission corrigée » possible. */
    public function test_aucune_route_ne_permet_de_reemettre_un_releve_modifie(): void
    {
        $this->mountTenantRoutes();

        $ecritures = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_contains($uri, 'invoice')) {
                continue;
            }
            $verbes = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            if ($verbes !== []) {
                $ecritures[] = implode('|', $verbes) . ' ' . $uri;
            }
        }

        $this->assertSame([], $ecritures, 'une route d’écriture sur les relevés est apparue : un relevé émis ne se modifie pas (D8)');
    }
}
