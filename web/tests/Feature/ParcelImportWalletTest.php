<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Imports\ParcelImport;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
use App\Exceptions\UnpricedDeliveryException;
use App\Models\Backend\DeliveryZone;
use App\Models\MerchantShops;
use App\Services\Parcel\ChargeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * L'import Excel de colis : facturation, société, et à qui appartient la ligne.
 *
 * Relevé en fermant le chantier du contrôle de solde (§15 de la revue) :
 * `ParcelImport` créait les colis **directement**, sans passer par aucun
 * repository, et ne touchait jamais le portefeuille — ni contrôle, ni débit.
 * Pour un marchand au portefeuille prépayé, le débit à la création *est* la
 * facturation : un import de deux cents colis n'était facturé nulle part.
 *
 * Deux défauts découverts en ouvrant le fichier, tous deux constatés avant
 * correction :
 *
 *   - les colis importés n'avaient **pas de `company_id`** — le champ n'était
 *     même pas assignable sur `Parcel` — donc restaient invisibles à tous les
 *     écrans `companywise()` : créés, non facturés, et introuvables ;
 *   - le marchand venait de la colonne `merchant_id` du **fichier**, sans
 *     aucun contrôle : un marchand qui y écrivait l'identifiant d'un autre
 *     créait des colis à son compte, et aurait vidé son portefeuille une fois
 *     le débit branché.
 *
 * Tout ou rien : Laravel Excel enveloppe déjà l'import dans une transaction.
 * Un refus à la ligne 2 annule la ligne 1 — comportement d'origine pour les
 * erreurs de validation, étendu au refus de solde.
 */
class ParcelImportWalletTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        $this->merchant->wallet_use_activation = Status::ACTIVE;
        $this->merchant->wallet_balance = 100000;
        $this->merchant->save();
    }

    /**
     * Les colonnes des deux fichiers modèles réellement distribués par les
     * écrans (`public/sample-parcel/`). Ils n'ont pas les mêmes : celui du
     * panneau marchand ne porte ni `shop_id`, ni `merchant_id` — le marchand
     * n'a rien à désigner, et c'est le socle qui fixe catégorie et type.
     *
     * Les tests écrivent ces colonnes-là, et pas un jeu inventé : c'est ce que
     * les marchands déposeront vraiment.
     */
    private const COLONNES_MARCHAND = [
        'customer_name', 'customer_phone', 'customer_address', 'customer_lat',
        // D4, étape 6 : la zone est une colonne du fichier comme les autres.
        'customer_long', 'invoice_no', 'zone_code', 'weight', 'cash_collection', 'selling_price', 'note',
    ];

    private const COLONNES_BACKOFFICE = [
        'merchant_id', 'shop_id', 'pickup_address', 'pickup_phone', 'pickup_lat',
        'pickup_long', 'customer_name', 'customer_phone', 'customer_address',
        'customer_lat', 'customer_long', 'invoice_no', 'category_id', 'zone_code', 'weight',
        'delivery_type_id', 'cash_collection', 'selling_price', 'packaging_id',
        'liquid_fragile', 'note',
    ];

    private function ligne(array $surcharges = []): array
    {
        return $surcharges + [
            'shop_id' => MerchantShops::firstOrFail()->id,
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_code' => DeliveryZone::COTONOU,
            'weight' => 1,
            'cash_collection' => 50000,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022997000031',
            'customer_address' => 'Cotonou, Akpakpa',
        ];
    }

    private function fichier(array $lignes, array $colonnes): string
    {
        $chemin = sys_get_temp_dir() . '/import-' . uniqid() . '.csv';
        $f = fopen($chemin, 'w');
        fputcsv($f, $colonnes);
        foreach ($lignes as $ligne) {
            fputcsv($f, array_map(fn ($colonne) => $ligne[$colonne] ?? '', $colonnes));
        }
        fclose($f);

        return $chemin;
    }

    private function importer(array $lignes, ?array $colonnes = null): void
    {
        (new ParcelImport())->import(
            $this->fichier($lignes, $colonnes ?? self::COLONNES_MARCHAND),
            null,
            Excel::CSV,
        );
    }

    /**
     * Les frais que le serveur applique à la ligne du scénario.
     *
     * ⚠️ Quand c'est un marchand qui importe, le socle **ignore** la catégorie
     * et le type de livraison du fichier : catégorie 1, type 2 (lendemain).
     * Comportement d'origine, conservé — le back-office, lui, lit le fichier.
     * La **zone**, elle, est lue dans les deux cas depuis l'étape 6 : c'est
     * elle qui tarife.
     */
    private function frais(?string $zone = null): float
    {
        $zoneId = DeliveryZone::where('company_id', $this->merchant->company_id)
            ->where('code', $zone ?? DeliveryZone::COTONOU)->value('id');

        return (float) app(ChargeCalculator::class)->calculate(
            $this->merchant->fresh(),
            1,
            1,
            50000.0,
            null,
            false,
            $zoneId,
        )['total_delivery_amount'];
    }

    private function soldeActuel(): float
    {
        return (float) Merchant::find($this->merchant->id)->wallet_balance;
    }

    // ---- Q1 : l'import doit-il débiter ? ---------------------------------

    /**
     * Oui : c'est la même facturation que la création unitaire, et l'écriture
     * de portefeuille suit.
     */
    public function test_importing_debits_the_wallet(): void
    {
        $this->actingAs($this->merchant->user->fresh());

        $this->importer([$this->ligne(), $this->ligne()]);

        $this->assertSame(2, Parcel::count());
        $this->assertSame(100000 - 2 * $this->frais(), $this->soldeActuel());
        $this->assertSame(2, Wallet::count());
    }

    /**
     * Le colis importé porte enfin sa société. Sans elle il était invisible à
     * tous les écrans du back-office : le transporteur ne le voyait pas pour
     * l'affecter à un ramassage.
     */
    public function test_an_imported_parcel_belongs_to_a_company(): void
    {
        $this->actingAs($this->merchant->user->fresh());

        $this->importer([$this->ligne()]);

        $this->assertSame($this->merchant->company_id, Parcel::firstOrFail()->company_id);
        $this->assertSame(1, Parcel::companywise()->count());
    }

    /** Un marchand hors portefeuille n'est ni débité, ni bloqué. */
    public function test_a_merchant_who_does_not_pay_by_wallet_is_not_debited(): void
    {
        $this->merchant->wallet_use_activation = Status::INACTIVE;
        $this->merchant->wallet_balance = 0;
        $this->merchant->save();

        $this->actingAs($this->merchant->user->fresh());
        $this->importer([$this->ligne()]);

        $this->assertSame(1, Parcel::count());
        $this->assertSame(0.0, $this->soldeActuel());
        $this->assertSame(0, Wallet::count());
    }

    /**
     * Retirer une colonne facultative ne doit pas tuer l'import.
     *
     * Chaque colonne était lue crûment (`$row['note']`) : un fichier allégé
     * levait une `ErrorException` au lieu d'un message utilisable. Seules
     * `cash_collection`, `customer_name` et `customer_address` sont vraiment
     * exigées côté marchand.
     */
    public function test_a_file_without_the_optional_columns_still_imports(): void
    {
        $this->actingAs($this->merchant->user->fresh());

        $this->importer(
            [$this->ligne()],
            ['customer_name', 'customer_phone', 'customer_address', 'cash_collection', 'zone_code'],
        );

        $this->assertSame(1, Parcel::count());
    }

    /**
     * La zone n'est **pas** une colonne facultative depuis l'étape 6 (D4).
     *
     * C'est la conséquence visible du retrait des quatre colonnes : un fichier
     * qui ne dit pas où va le colis ne peut plus être tarifé, et l'import
     * s'arrête plutôt que de facturer au hasard. Le modèle livré dans
     * `public/sample-parcel/` porte la colonne ; un ancien fichier doit être
     * complété.
     */
    public function test_a_file_without_a_zone_imports_nothing(): void
    {
        $this->actingAs($this->merchant->user->fresh());

        $this->expectException(UnpricedDeliveryException::class);

        $this->importer(
            [$this->ligne()],
            ['customer_name', 'customer_phone', 'customer_address', 'cash_collection'],
        );

        $this->assertSame(0, Parcel::count());
    }

    // ---- Q2 : refus total, ou lignes couvertes ? -------------------------

    /**
     * Tout ou rien. Le solde ne couvre que la première ligne : la seconde est
     * refusée, et la première disparaît avec elle.
     *
     * Un import à moitié passé serait indiscernable d'un import complet — même
     * message de succès, même écran — et le marchand qui relance son fichier
     * pour « finir » duplique ce qui était déjà entré. Rien dans le fichier ne
     * permet de dédoublonner.
     */
    public function test_a_file_the_wallet_cannot_cover_imports_nothing(): void
    {
        // Se connecter d'abord : `frais()` résout la zone par `companywise()`,
        // qui retombe sur la société 1 hors session authentifiée.
        $this->actingAs($this->merchant->user->fresh());

        $this->merchant->wallet_balance = $this->frais() * 1.5;
        $this->merchant->save();
        $solde = $this->soldeActuel();

        try {
            $this->importer([$this->ligne(), $this->ligne()]);
            $this->fail('Le solde ne couvrait pas les deux lignes.');
        } catch (InsufficientWalletBalance $e) {
            $this->assertSame($this->frais() * 0.5, $e->available);
        }

        $this->assertSame(0, Parcel::count(), 'La premiere ligne aurait du etre annulee avec la seconde.');
        $this->assertSame(0, Wallet::count());
        $this->assertSame($solde, $this->soldeActuel());
    }

    /**
     * Le comportement d'origine sur une ligne invalide est le même — c'est de
     * là que vient la règle, on ne fait que l'étendre au solde.
     */
    public function test_an_invalid_row_still_cancels_the_whole_file(): void
    {
        $this->actingAs($this->merchant->user->fresh());

        $this->expectException(ValidationException::class);

        try {
            $this->importer([$this->ligne(), $this->ligne(['customer_address' => ''])]);
        } finally {
            $this->assertSame(0, Parcel::count());
        }
    }

    // ---- La faille ouverte par le débit ----------------------------------

    /**
     * Un marchand n'importe que pour lui-même. Le socle lisait la colonne
     * `merchant_id` du fichier avant toute chose : y écrire l'identifiant d'un
     * voisin créait les colis à son compte — et, le débit branché, aurait vidé
     * son portefeuille.
     */
    public function test_a_merchant_cannot_import_on_behalf_of_another(): void
    {
        $voisin = $this->voisin();

        // Le modele du panneau marchand ne porte PAS `merchant_id` : la
        // colonne est ajoutee a la main, ce qui est precisement le geste.
        $this->actingAs($this->merchant->user->fresh());
        $this->importer(
            [$this->ligne(['merchant_id' => $voisin->id])],
            array_merge(['merchant_id'], self::COLONNES_MARCHAND),
        );

        $this->assertSame($this->merchant->id, Parcel::firstOrFail()->merchant_id);
        $this->assertSame(0, Wallet::where('merchant_id', $voisin->id)->count());
        $this->assertSame(100000.0, (float) Merchant::find($voisin->id)->wallet_balance);
    }

    /**
     * Le back-office, lui, désigne bien un marchand — mais de sa société. Un
     * identifiant d'ailleurs est refusé par la validation, donc rapporté ligne
     * par ligne à l'écran, comme les autres erreurs de fichier.
     */
    public function test_the_backoffice_cannot_import_for_another_company(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();

        $this->actingAs($this->administrateur());

        try {
            $this->importer([$this->ligne(['merchant_id' => $ailleurs->id])], self::COLONNES_BACKOFFICE);
            $this->fail('Un marchand d\'une autre societe aurait du etre refuse.');
        } catch (ValidationException $e) {
            // Refus sur `merchant_id` precisement, pas sur une autre colonne.
            $this->assertSame('merchant_id', $e->failures()[0]->attribute());
        }

        $this->assertSame(0, Parcel::count());
    }

    /** Le back-office importe normalement pour un marchand de chez lui. */
    public function test_the_backoffice_imports_for_a_merchant_of_its_own_company(): void
    {
        $this->actingAs($this->administrateur());

        $this->importer([$this->ligne(['merchant_id' => $this->merchant->id])], self::COLONNES_BACKOFFICE);

        $this->assertSame($this->merchant->id, Parcel::firstOrFail()->merchant_id);
        // Le back-office comme le marchand facturent par la zone du fichier :
        // depuis l'étape 6, c'est elle qui donne le tarif, pas le type.
        $this->assertSame(100000 - $this->frais(), $this->soldeActuel());
        $this->assertNotNull(Parcel::firstOrFail()->zone_id, 'le colis importé porte sa zone');
    }

    // ---- fixtures --------------------------------------------------------

    /** Un administrateur : un compte sans `merchant` rattaché. */
    private function administrateur(): \App\Models\User
    {
        $admin = $this->merchant->user->replicate();
        $admin->email = 'admin@example.test';
        $admin->mobile = '0022997000077';
        $admin->unique_id = 'U-ADMIN';
        $admin->save();

        return $admin->fresh();
    }

    private function voisin(): Merchant
    {
        $user = $this->merchant->user->replicate();
        $user->email = 'voisin@example.test';
        $user->mobile = '0022997000009';
        $user->unique_id = 'U-VOISIN';
        $user->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $user->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->wallet_balance = 100000;
        $voisin->save();

        $boutique = MerchantShops::firstOrFail()->replicate();
        $boutique->merchant_id = $voisin->id;
        $boutique->save();

        return $voisin->fresh();
    }

    private function marchandDUneAutreSociete(): Merchant
    {
        $societe = new GeneralSettings();
        $societe->forceFill(['name' => 'Autre transporteur', 'status' => Status::ACTIVE, 'currency' => 'XOF'])->save();

        $user = $this->merchant->user->replicate();
        $user->email = 'ailleurs@example.test';
        $user->mobile = '0022997000010';
        $user->unique_id = 'U-AILLEURS';
        $user->company_id = $societe->id;
        $user->save();

        $ailleurs = $this->merchant->replicate();
        $ailleurs->user_id = $user->id;
        $ailleurs->merchant_unique_id = 'M-AILLEURS';
        $ailleurs->company_id = $societe->id;
        $ailleurs->save();

        return $ailleurs->fresh();
    }
}
