<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Payment;
use App\Models\User;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S63 — les onze dernières routes de l'arriéré du filet S58 (11 → 0).
 *
 * Aucun défaut dans ce lot : les onze étaient bornées. Elles restaient à
 * l'arriéré parce que **lu n'est pas prouvé**, et c'est cette dette-là que ce
 * fichier solde.
 *
 * ⚠️ Une **correction de ma propre lecture** mérite d'être écrite ici :
 * `merchantparcelTotalSummeryReports()` m'a paru ne borner que par société,
 * alors que son voisin `merchantParcelReports()` ajoute le marchand connecté.
 * C'était FAUX : le filtre `merchant_id` est bien là, onze lignes plus bas,
 * **dans la fermeture**. J'avais conclu après neuf lignes.
 */
class RemainingSurfaceScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    /* ───────────────────────── le back-office ───────────────────────────── */

    public function test_the_customs_alerts_stay_inside_the_company(): void
    {
        $this->alerteDe(settings()->id, 'MIEN');
        $this->alerteDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/customs/alerts', 'parcel_read', []);

        $this->assertStringContainsString('SUIVI-S63-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('SUIVI-S63-SIEN', $page,
            'les alertes douanières rendent le colis d\'une AUTRE société');
    }

    public function test_the_delivery_charge_filter_stays_inside_the_company(): void
    {
        $this->baremeDe(settings()->id, 'MIEN', 12345);
        $this->baremeDe(self::AUTRE, 'SIEN', 67890);

        $page = $this->ecran('/admin/delivery-charge/filter', 'delivery_charge_read', ['weight' => 7]);

        $this->assertStringContainsString(formatAmount(12345), $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString(formatAmount(67890), $page,
            'la grille tarifaire rend le barème d\'une AUTRE société');
    }

    public function test_the_merchant_payment_filter_stays_inside_the_company(): void
    {
        $this->versementDe(settings()->id, 45678);
        $this->versementDe(self::AUTRE, 98765);

        $page = $this->ecran('/admin/payment/merchant/filter', 'payment_read', [
            'date' => $this->plage(),
        ]);

        $this->assertStringContainsString(formatAmount(45678), $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString(formatAmount(98765), $page,
            'le filtre des versements rend celui d\'une AUTRE société');
    }

    public function test_the_syscohada_journal_stays_inside_the_company(): void
    {
        $this->factureDe(settings()->id, 'MIEN');
        $this->factureDe(self::AUTRE, 'SIEN');

        $csv = $this->ecran('/admin/paid/invoice/syscohada-journal', 'invoice_read', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]);

        // ⚠️ Le CSV porte le NOM DU MARCHAND (`$statement['merchant']['name']`),
        // pas le numero de facture : c'est lui le marqueur.
        $this->assertStringContainsString('PME-S63-MIEN', $csv, 'contrôle positif tombé');
        $this->assertStringNotContainsString('PME-S63-SIEN', $csv,
            'le journal SYSCOHADA exporte la facture d\'une AUTRE société');
    }

    /**
     * La grille des zones ne rend que des **montants de tranche** : elle se
     * prouve sur la collection, comme les deux écrans d'agrégats de S62.
     */
    public function test_the_zone_grid_stays_inside_the_company(): void
    {
        $mienne = $this->baremeDe(settings()->id, 'MIEN', 12345);

        // ⚠️ Le barème d'en face doit porter LA MÊME catégorie que le nôtre.
        // Avec deux catégories différentes, le filtre `category_id` suffisait à
        // l'exclure et le sabotage restait VERT : le test ne mesurait pas la
        // portée, il mesurait le filtre.
        $this->baremeDe(self::AUTRE, 'SIEN', 67890, $mienne->category_id);

        $this->actingAs($this->agentDe(settings()->id));

        $montants = collect(app(DeliveryZoneInterface::class)->tranches($mienne->category_id))
            ->pluck('amounts')->flatten()->all();

        $this->assertContains(12345.0, $montants, 'contrôle positif tombé');
        $this->assertNotContains(67890.0, $montants,
            'la grille des zones rend le barème d\'une AUTRE société');
    }

    public function test_the_wallet_requests_stay_inside_the_company(): void
    {
        $this->rechargeDe(settings()->id, 'MIEN');
        $this->rechargeDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/wallet-request', 'wallet_request_read', []);

        $this->assertStringContainsString('PME-S63-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('PME-S63-SIEN', $page,
            'les demandes de recharge rendent le marchand d\'une AUTRE société');
    }

    /* ─────────────────────── le panneau marchand ─────────────────────────── */

    public function test_the_merchant_parcel_filter_shows_only_its_own(): void
    {
        $page = $this->ecranMarchand('/merchant/parcel/filter', [
            'parcel_date' => $this->plage(),
        ]);

        $this->assertStringContainsString('CLIENT-S63-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S63-SIEN', $page,
            'le panneau marchand rend le colis d\'un AUTRE marchand');
    }

    /**
     * ⚠️ Cet écran ne rend que des **compteurs** (`$parcel->count`) : il se prouve
     * sur la collection, comme les deux écrans d'agrégats de S62. Mon premier jet
     * cherchait un numéro de suivi dans la page — le contrôle positif l'a dit.
     */
    public function test_the_merchant_report_shows_only_its_own(): void
    {
        $mien = $this->marchandDe(settings()->id, 'MIEN');
        $voisin = $this->marchandDe(settings()->id, 'SIEN');
        $aMoi = $this->colisPour($mien, 'MIEN');
        $aLui = $this->colisPour($voisin, 'SIEN');

        $this->actingAs($mien->user);

        $ids = app(\App\Repositories\Reports\ReportsInterface::class)
            ->merchantParcelReports(new \Illuminate\Http\Request(['parcel_date' => $this->plage()]))
            ->flatten()->pluck('id')->all();

        $this->assertContains($aMoi->id, $ids, 'contrôle positif tombé');
        $this->assertNotContains($aLui->id, $ids,
            'le rapport du panneau marchand compte le colis d\'un AUTRE marchand');
    }

    public function test_the_merchant_export_shows_only_its_own(): void
    {
        $fichier = $this->ecranMarchand('/merchant/parcel/file-export', ['type' => 'csv']);

        $this->assertStringContainsString('CLIENT-S63-MIEN', $fichier, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S63-SIEN', $fichier,
            'l\'export du panneau marchand contient le colis d\'un AUTRE marchand');
    }

    public function test_the_merchant_wallet_shows_only_its_own(): void
    {
        $mien = $this->marchandDe(settings()->id, 'MIEN');
        $voisin = $this->marchandDe(settings()->id, 'SIEN');
        $maRecharge = $this->rechargePour($mien);
        $laSienne = $this->rechargePour($voisin);

        $reponse = $this->actingAs($mien->user)->get(self::HOTE . '/merchant/my-wallet');
        $reponse->assertOk();
        $page = $this->corps($reponse);

        // ⚠️ Cet ecran rend le NUMERO DE TRANSACTION, pas le nom du marchand :
        // c'est lui le marqueur (le voisin de l'ecran d'administration, lui,
        // rend bien `merchant->business_name`).
        $this->assertStringContainsString($maRecharge->transaction_id, $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString($laSienne->transaction_id, $page,
            'le portefeuille du marchand rend la recharge d\'un AUTRE marchand');
    }

    /**
     * Le récapitulatif du panneau marchand ne rend que des **agrégats** : il se
     * prouve sur la collection, comme ses voisins de S62.
     */
    public function test_the_merchant_total_summary_shows_only_its_own(): void
    {
        $mien = $this->marchandDe(settings()->id, 'MIEN');
        $voisin = $this->marchandDe(settings()->id, 'SIEN');
        $aMoi = $this->colisPour($mien, 'MIEN');
        $aLui = $this->colisPour($voisin, 'SIEN');

        $this->actingAs($mien->user);

        $ids = app(\App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface::class)
            ->merchantparcelTotalSummeryReports(new \Illuminate\Http\Request(['parcel_date' => $this->plage()]))
            ->pluck('id')->all();

        $this->assertContains($aMoi->id, $ids, 'contrôle positif tombé');
        $this->assertNotContains($aLui->id, $ids,
            'le récapitulatif du panneau marchand totalise le colis d\'un AUTRE marchand');
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    private function ecran(string $uri, string $droit, array $parametres): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = [$droit];
        $agent->save();

        $reponse = $this->actingAs($agent)->get(self::HOTE . $uri . '?' . http_build_query($parametres));
        $reponse->assertOk();

        return $this->corps($reponse);
    }

    /**
     * Le panneau marchand : le marchand connecté a UN colis, un voisin de la
     * MÊME société en a un autre. Ce que ces écrans doivent cloisonner ici n'est
     * pas la société — c'est le **marchand**.
     */
    private function ecranMarchand(string $uri, array $parametres): string
    {
        $mien = $this->marchandDe(settings()->id, 'MIEN');
        $voisin = $this->marchandDe(settings()->id, 'SIEN');

        $this->colisPour($mien, 'MIEN');
        $this->colisPour($voisin, 'SIEN');

        $reponse = $this->actingAs($mien->user)->get(self::HOTE . $uri . '?' . http_build_query($parametres));
        $reponse->assertOk();

        return $this->corps($reponse);
    }

    /**
     * ⚠️ Trois natures de reponse dans ce lot : une page HTML, un CSV en FLUX
     * (`StreamedResponse`) et un tableur en FICHIER (`BinaryFileResponse`).
     * `streamedContent()` LEVE sur une reponse non diffusee, et `getContent()`
     * rend `false` sur un fichier binaire. Il faut donc demander a la reponse ce
     * qu'elle est avant de lui demander son corps.
     */
    private function corps(\Illuminate\Testing\TestResponse $reponse): string
    {
        $base = $reponse->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $reponse->streamedContent();
        }

        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        return (string) $reponse->getContent();
    }

    private function plage(): string
    {
        return now()->subDay()->toDateString() . 'To' . now()->addDay()->toDateString();
    }

    private function alerteDe(int $societe, string $marque): \App\Models\Backend\CustomsAlert
    {
        $colis = $this->colisPour($this->marchandDe($societe, $marque), $marque);

        return \App\Models\Backend\CustomsAlert::forceCreate([
            'company_id' => $societe,
            'parcel_id' => $colis->id,
            'merchant_id' => $colis->merchant_id,
            'status' => \App\Enums\CustomsAlertStatus::PENDING,
            'country_code' => 'FR',
            'country_name' => 'France',
            'goods_category' => 'general',
            'level' => 1,
            'message' => 'Motif S63 ' . $marque,
        ]);
    }

    private function baremeDe(int $societe, string $marque, int $montant, ?int $categorieId = null): DeliveryCharge
    {
        $categorieId ??= Deliverycategory::forceCreate([
            'company_id' => $societe, 'title' => 'CAT-S63-' . $marque,
            'status' => Status::ACTIVE, 'position' => 1,
        ])->id;

        $zone = app(\App\Services\Pricing\ZoneCatalog::class)->installer($societe)[\App\Models\Backend\DeliveryZone::COTONOU];

        return DeliveryCharge::forceCreate([
            'company_id' => $societe, 'category_id' => $categorieId,
            'zone_id' => $zone->id, 'weight' => 7, 'amount' => $montant,
            'position' => 7, 'status' => Status::ACTIVE,
        ]);
    }

    private function versementDe(int $societe, int $montant): Payment
    {
        return Payment::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $this->marchandDe($societe, 'PAIE')->id,
            'amount' => $montant,
            'status' => 1,
            'created_at' => now(),
        ]);
    }

    private function factureDe(int $societe, string $marque): Invoice
    {
        $marchand = $this->marchandDe($societe, $marque);
        // ⚠️ Sans FRAIS sur le colis, `fees_ttc` vaut 0 et `SyscohadaJournal`
        // n'emet AUCUNE ligne : le CSV se reduit a son en-tete, et le controle
        // positif tombe. C'est lui qui me l'a appris.
        $colis = $this->colisPour($marchand, $marque);
        $colis->total_delivery_amount = 2360;
        $colis->vat_amount = 360;
        $colis->save();

        $facture = Invoice::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $marchand->id,
            'invoice_id' => 'FACT-S63-' . $marque,
            'invoice_date' => now()->toDateString(),
            'total_charge' => 5000,
            'cash_collection' => 50000,
            'current_payable' => 45000,
            'parcels_id' => [$colis->id],
            'fiscal_year' => (int) now()->format('Y'),
            'sequence' => $societe,
            'issued_on' => now()->toDateString(),
            'status' => 1,
        ]);

        // ⚠️ Le releve ne lit PAS la colonne `parcels_id` : il boucle sur
        // `invoiceParcels()`, une table a part. Sans ligne ici, `fees_ttc` vaut 0
        // et le journal n'emet rien — le CSV se reduit a son en-tete.
        \App\Models\Backend\InvoiceParcel::forceCreate([
            'company_id' => $societe,
            'invoice_id' => $facture->id,
            'parcel_id' => $colis->id,
            'parcel_status' => ParcelStatus::DELIVERED,
            'total_delivery_amount' => 2360,
            'collected_amount' => 10000,
            'return_charge' => 0,
            'vat_amount' => 360,
            'cod_amount' => 0,
            'total_charge_amount' => 2360,
            'current_payable' => 7640,
        ]);

        return $facture;
    }

    private function colisPour(Merchant $marchand, string $marque): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'SUIVI-S63-' . $marque,
            'customer_name' => 'CLIENT-S63-' . $marque,
            'customer_phone' => '0022997' . ord($marque[0]) . '000',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => ParcelStatus::PENDING,
            'priority_type_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function marchandDe(int $societe, string $marque): Merchant
    {
        $existant = Merchant::where('company_id', $societe)
            ->where('business_name', 'PME-S63-' . $marque)->first();

        if ($existant) {
            return $existant;
        }

        $u = $this->agentDe($societe);
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME-S63-' . $marque, 'current_balance' => 0,
            'opening_balance' => 0, 'wallet_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function rechargeDe(int $societe, string $marque): \App\Models\Backend\Wallet
    {
        return $this->rechargePour($this->marchandDe($societe, $marque));
    }

    private function rechargePour(Merchant $marchand): \App\Models\Backend\Wallet
    {
        return \App\Models\Backend\Wallet::forceCreate([
            'company_id' => $marchand->company_id,
            'user_id' => $marchand->user_id,
            'merchant_id' => $marchand->id,
            'transaction_id' => 'TRX-S63-' . $marchand->id,
            'amount' => 25000,
            'type' => 1,
            'source' => 1,
            'payment_method' => 'cash',
            'status' => 1,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S63';
        $agent->email = 'agent.s63.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
