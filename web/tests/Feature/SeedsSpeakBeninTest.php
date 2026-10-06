<?php

namespace Tests\Feature;

use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S92** — les semences parlent du Bénin.
 *
 * Le socle We Courier amorçait une installation avec l'identité de son éditeur :
 * agences de Dhaka, adresses de Mirpur, banque et mobile money bangladais,
 * raison sociale « WemaxDevs ». Un transporteur béninois qui ouvrait son
 * back-office le premier jour lisait un autre pays. Depuis S92 la plateforme
 * s'appelle BeninLink, la société de démonstration est un transporteur de
 * Cotonou, les six agences sont des villes du réseau visé, les numéros ont
 * dix chiffres (plan de numérotation 2024) et les comptes mobile money sont
 * ceux que FedaPay sert (MTN MoMo, Moov Money).
 *
 * Les **courriels d'amorçage ne changent pas** : `SeedAccounts::COMPTES` (S87)
 * les nomme pour refuser un déploiement au mot de passe public.
 */
class SeedsSpeakBeninTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AILLEURS = ['Dhaka', 'Mirpur', 'Bangladesh', 'Wemax', '+88', 'Bkash', 'Nagad', 'Nogod', 'Rocket', 'NRB'];

    /** Dix chiffres commençant par 01 (Bénin, depuis le 30 novembre 2024), indicatif optionnel. */
    private const TELEPHONE = '/^(\+229)?01\d{8}$/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    private function assertParleDuBenin(string $valeur, string $quoi): void
    {
        foreach (self::AILLEURS as $mot) {
            $this->assertStringNotContainsStringIgnoringCase($mot, $valeur, "{$quoi} : « {$valeur} » parle d'ailleurs");
        }
    }

    public function test_the_platform_and_the_demo_carrier_are_beninese(): void
    {
        $societes = GeneralSettings::orderBy('id')->get();
        $this->assertCount(2, $societes, 'fixture : la plateforme et la société de démonstration');

        $this->assertSame('BeninLink', $societes[0]->name);
        foreach ($societes as $societe) {
            foreach (['name', 'email', 'address', 'copyright'] as $champ) {
                $this->assertParleDuBenin((string) $societe->{$champ}, "société {$societe->id} · {$champ}");
            }
            $this->assertStringContainsString('Bénin', $societe->address);
            $this->assertMatchesRegularExpression(self::TELEPHONE, $societe->phone);
            $this->assertSame('FCFA', $societe->currency);
        }
    }

    public function test_the_six_hubs_are_cities_of_the_network(): void
    {
        $agences = Hub::orderBy('id')->get();
        $this->assertCount(6, $agences, 'les autres semences lisent les ids 1 à 6');

        foreach ($agences as $agence) {
            $this->assertParleDuBenin($agence->name . ' ' . $agence->address, "agence {$agence->id}");
            $this->assertStringContainsString('Bénin', $agence->address);
            $this->assertMatchesRegularExpression(self::TELEPHONE, $agence->phone);
        }
        $this->assertStringStartsWith('Cotonou', $agences[0]->name, 'l\'agence 1, celle du livreur de démonstration, est à Cotonou');
    }

    public function test_the_seeded_people_live_in_benin_and_keep_their_login(): void
    {
        $this->assertGreaterThanOrEqual(4, User::count());
        foreach (User::all() as $utilisateur) {
            $this->assertParleDuBenin((string) $utilisateur->address, "utilisateur {$utilisateur->email} · adresse");
            $this->assertStringContainsString('Bénin', (string) $utilisateur->address);
            $this->assertMatchesRegularExpression(self::TELEPHONE, (string) $utilisateur->mobile, $utilisateur->email);
        }
        // Les identifiants d'amorçage sont ceux que S87 surveille : ils ne bougent pas.
        foreach (\App\Services\Install\SeedAccounts::COMPTES as $email) {
            if ($email === 'deliveryman@wemaxit.com') {
                continue; // semé par DeliveryManSeeder, hors du préfixe joué ici
            }
            $this->assertTrue(User::where('email', $email)->exists(), $email);
        }

        $marchand = Merchant::firstOrFail();
        $this->assertSame('Boutique Démo Cotonou', $marchand->business_name);
        $this->assertParleDuBenin((string) $marchand->address, 'marchand · adresse');

        foreach (MerchantShops::all() as $boutique) {
            $this->assertParleDuBenin($boutique->address, "boutique {$boutique->id}");
            $this->assertMatchesRegularExpression(self::TELEPHONE, $boutique->contact_no);
        }
    }

    /**
     * Les semences hors du préfixe joué par la suite (comptes bancaires, mobile
     * money, colis de démonstration, vitrine) sont lues à la source : seul le
     * catalogue mondial des devises garde le taka. Les semences de la vitrine
     * (`Backend/FrontWeb`) et du super-admin sont lues aussi.
     */
    public function test_no_seeder_but_the_currency_catalogue_mentions_bangladesh(): void
    {
        $fichiers = array_merge(glob(database_path('seeders/*.php')), glob(database_path('seeders/*/*/*.php')));
        $this->assertNotEmpty($fichiers);

        foreach ($fichiers as $fichier) {
            if (basename($fichier) === 'CurrencySeeder.php') {
                continue;
            }
            $source = file_get_contents($fichier);
            foreach (['Dhaka', 'Mirpur', 'Bangladesh', '+88', 'Bkash', 'Nagad', 'Rocket', 'NRB Commercial', 'Wemaxdevs,', '"WemaxDevs"'] as $mot) {
                $this->assertStringNotContainsString($mot, $source, basename($fichier) . " : « {$mot} »");
            }
        }

        foreach (['seeders/CompanyFrontendDataSeeder.php', 'seeders/Backend/FrontWeb/SectionSeeder.php'] as $vitrine) {
            $this->assertStringContainsString('6.3703,2.3912', file_get_contents(database_path($vitrine)), "{$vitrine} : la carte de la vitrine pointe Cotonou");
        }
        $comptes = file_get_contents(database_path('seeders/PaymentAccountSeeder.php'));
        $this->assertStringContainsString("['MTN MoMo','Moov Money']", $comptes, 'les comptes mobile money sont ceux que FedaPay sert');
    }
}
