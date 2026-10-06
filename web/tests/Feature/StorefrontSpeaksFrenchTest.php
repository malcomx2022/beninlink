<?php

namespace Tests\Feature;

use App\Models\Backend\FrontWeb\Blog;
use App\Models\Backend\FrontWeb\Faq;
use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\FrontWeb\SocialLink;
use App\Models\Backend\FrontWeb\WhyCourier;
use Database\Seeders\Backend\FrontWeb\BlogSeeder;
use Database\Seeders\Backend\FrontWeb\FaqSeeder;
use Database\Seeders\Backend\FrontWeb\PageSeeder;
use Database\Seeders\Backend\FrontWeb\PartnerSeeder;
use Database\Seeders\Backend\FrontWeb\SectionSeeder;
use Database\Seeders\Backend\FrontWeb\ServiceSeeder;
use Database\Seeders\Backend\FrontWeb\SocialLinkSeeder;
use Database\Seeders\Backend\FrontWeb\WhyCourierSeeder;
use Database\Seeders\CompanyFrontendDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S93** — la vitrine d'une société neuve parle français.
 *
 * `CompanyRepository` remplit la vitrine publique de chaque société que le
 * super-admin crée (`CompanyFrontendDataSeeder::companySiteData`). Le socle y
 * mettait des titres anglais, des paragraphes *lorem ipsum* (`Faker`), des
 * compteurs inventés et des pages légales sans contenu ; la société 1 répétait
 * le même texte en SQL MySQL brut que la suite ne pouvait pas jouer. Ce filet
 * joue les deux chemins sur SQLite et lit ce qu'un visiteur lirait.
 */
class StorefrontSpeaksFrenchTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    /** Ce qu'un visiteur ne doit plus lire : l'anglais du socle et le lorem ipsum de Faker. */
    private const AILLEURS = ['Delivery', 'Subscribe', 'About Us', 'Happy', 'Branches', 'Privacy', 'Terms', 'Contact Us', 'wecourier', 'Deleniti', 'dolorem', 'Lorem', 'Pick & Drop', 'Warehousing'];

    /** La société de démonstration (2) reçoit sa vitrine dans `UserSeeder` par ce même chemin ; 3 est « une société que le super-admin crée ». */
    private const DEMO = 2;

    /** « Une société que le super-admin crée » : une ligne de `general_settings` copiée sur la démo. */
    private int $societe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();

        $ligne = (array) DB::table('general_settings')->find(self::DEMO);
        unset($ligne['id']);
        $ligne['name'] = 'Société neuve';
        $this->societe = (int) DB::table('general_settings')->insertGetId($ligne);
    }

    private function assertEnFrancais(string $texte, string $quoi): void
    {
        $this->assertNotSame('', trim($texte), "{$quoi} : vide");
        foreach (self::AILLEURS as $mot) {
            $this->assertStringNotContainsString($mot, $texte, "{$quoi} : « {$texte} » n'est pas la vitrine d'un transporteur béninois");
        }
    }

    /** Toutes les lignes de la vitrine d'une société : [quoi, texte]. */
    private function textes(int $societe): array
    {
        $lignes = [];
        foreach (Service::where('company_id', $societe)->orderBy('position')->get() as $s) {
            $lignes[] = ["service {$s->position}", $s->title . ' ' . $s->description];
        }
        foreach (WhyCourier::where('company_id', $societe)->orderBy('position')->get() as $a) {
            $lignes[] = ["atout {$a->position}", $a->title];
        }
        foreach (Faq::where('company_id', $societe)->orderBy('position')->get() as $f) {
            $lignes[] = ["question {$f->position}", $f->question . ' ' . $f->answer];
        }
        foreach (Blog::where('company_id', $societe)->orderBy('position')->get() as $b) {
            $lignes[] = ["article {$b->position}", $b->title . ' ' . $b->description];
        }
        foreach (Partner::where('company_id', $societe)->orderBy('position')->get() as $p) {
            $lignes[] = ["partenaire {$p->position}", $p->name];
        }
        foreach (DB::table('pages')->where('company_id', $societe)->orderBy('id')->get() as $p) {
            $lignes[] = ["page {$p->page}", $p->title . ' ' . $p->description];
        }
        foreach (DB::table('sections')->where('company_id', $societe)->whereNotNull('value')->orderBy('id')->get() as $s) {
            $lignes[] = ["section {$s->key}", $s->value];
        }

        return $lignes;
    }

    public function test_a_company_created_by_the_super_admin_gets_a_french_storefront(): void
    {
        (new CompanyFrontendDataSeeder)->companySiteData($this->societe);

        $textes = $this->textes($this->societe);
        $this->assertGreaterThan(40, count($textes), 'services, atouts, questions, articles, partenaires, pages et sections');
        foreach ($textes as [$quoi, $texte]) {
            $this->assertEnFrancais($texte, $quoi);
        }

        // Ce que les gabarits lisent existe : chaque clé de `section()` et chaque page nommée.
        $cles = DB::table('sections')->where('company_id', $this->societe)->pluck('key')->all();
        foreach (['title_1', 'title_2', 'title_3', 'sub_title', 'banner', 'branch_count', 'parcel_title', 'merchant_title', 'reviews_title', 'about_us', 'subscribe_title', 'subscribe_description', 'playstore_link', 'ios_link', 'map_link'] as $cle) {
            $this->assertContains($cle, $cles, "section « {$cle} »");
        }
        $this->assertEqualsCanonicalizing(
            ['privacy_policy', 'terms_conditions', 'about_us', 'faq', 'contact'],
            DB::table('pages')->where('company_id', $this->societe)->pluck('page')->all()
        );
        $this->assertSame('1', DB::table('sections')->where('company_id', $this->societe)->where('key', 'branch_count')->value('value'), 'une agence, pas 7 520');
        $this->assertSame('0', DB::table('sections')->where('company_id', $this->societe)->where('key', 'parcel_count')->value('value'), 'zéro colis livré le premier jour : le compteur ne s\'invente pas');
        $this->assertStringContainsString('6.3703,2.3912', DB::table('sections')->where('company_id', $this->societe)->where('key', 'map_link')->value('value'), 'la carte pointe Cotonou');

        // Tout est à la société qui vient d'être créée, et la société de démonstration (semée par le même chemin) a reçu la même chose.
        foreach (['services', 'why_couriers', 'faqs', 'blogs', 'partners', 'social_links', 'pages', 'sections'] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->where('company_id', $this->societe)->count(), $table);
            $this->assertSame(DB::table($table)->where('company_id', self::DEMO)->count(), DB::table($table)->where('company_id', $this->societe)->count(), $table);
            $this->assertSame(0, DB::table($table)->whereNotIn('company_id', [self::DEMO, $this->societe])->count(), "{$table} : rien hors des sociétés semées");
        }
        $this->assertCount(6, SocialLink::where('company_id', $this->societe)->get());
    }

    public function test_the_platform_storefront_is_the_same_french_content(): void
    {
        $this->seed([SocialLinkSeeder::class, ServiceSeeder::class, WhyCourierSeeder::class, FaqSeeder::class, PartnerSeeder::class, BlogSeeder::class, PageSeeder::class, SectionSeeder::class]);

        $plateforme = array_map(fn ($l) => $l[1], $this->textes(1));
        $societe    = array_map(fn ($l) => $l[1], $this->textes(self::DEMO));
        $this->assertNotEmpty($plateforme);
        $this->assertSame($societe, $plateforme, 'une seule source : la société 1 lit le même texte que toute société créée');
    }

    /** Ce qu'un visiteur lit sur la page d'accueil du transporteur de démonstration : la bannière, les atouts, le pied de page — en français. */
    public function test_the_public_home_page_reads_in_french(): void
    {
        // L'hôte de test est la société 1 (`settings()` hors requête) : sa vitrine vient des semences `FrontWeb`.
        $this->seed([SocialLinkSeeder::class, ServiceSeeder::class, WhyCourierSeeder::class, FaqSeeder::class, PartnerSeeder::class, BlogSeeder::class, PageSeeder::class, SectionSeeder::class]);
        $this->mountTenantRoutes();

        $page = $this->get(self::HOTE . '/')->assertOk()->getContent();

        foreach (['VOS COLIS', 'LIVRÉS AU BÉNIN', 'Livraison dans les délais', 'Paiement à la livraison', 'Restez informés', 'Colis livrés', 'Accueil'] as $attendu) {
            $this->assertStringContainsString($attendu, $page, "accueil : « {$attendu} »");
        }
        // « Maison » : la traduction mot à mot de Home que le socle livrait dans `lang/fr/levels.php`.
        foreach (['SUB-DOMAIN BASED', 'Subscribe Us', 'Happy Merchant', 'Timely Delivery', 'Deleniti', 'Maison'] as $socle) {
            $this->assertStringNotContainsString($socle, $page, "accueil : « {$socle} » est le texte du socle");
        }
    }

    /** Plus de SQL MySQL brut ni de Faker dans la vitrine : la suite peut la jouer, et un visiteur ne lit pas de lorem ipsum. */
    public function test_no_storefront_seeder_writes_raw_sql_or_lorem_ipsum(): void
    {
        $fichiers = array_merge([database_path('seeders/CompanyFrontendDataSeeder.php')], glob(database_path('seeders/Backend/FrontWeb/*.php')));
        $this->assertCount(9, $fichiers);

        foreach ($fichiers as $fichier) {
            $source = file_get_contents($fichier);
            $this->assertStringNotContainsString('DB::statement', $source, basename($fichier));
            $this->assertStringNotContainsString('Faker::', $source, basename($fichier));
            $this->assertStringNotContainsString('use Faker', $source, basename($fichier));
        }
    }
}
