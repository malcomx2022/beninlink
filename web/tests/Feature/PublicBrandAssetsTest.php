<?php

namespace Tests\Feature;

use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\Upload;
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
use Symfony\Component\Finder\Finder;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S111** — aucune marque tierce sur les pages publiques.
 *
 * La page d'accueil de beninlink.app montrait sous « Nos partenaires » les six
 * vignettes semées par le socle : Huawei, UPS, Digg, Atom et 500px (deux fois),
 * des marques réelles sans aucune relation avec BeninLink, soit une fausse
 * affiliation. La revue des autres pages publiques a trouvé le même défaut ailleurs :
 * le logo, le logo clair et le favicon par défaut (« WeCourier SAAS »), l'illustration
 * des pages de connexion et d'inscription (camions « We Courier DELIVERY »), et trois
 * copies mortes du logo de l'éditeur dans `public/frontend/`.
 *
 * Ce filet tient quatre choses : aucun de ces fichiers ne revient dans `public/`
 * (par empreinte, quel que soit son nom), aucune vue ne les nomme, aucune semence
 * ne pose de partenaire, et la page d'accueil n'affiche la section que si le
 * transporteur a saisi de vrais partenaires. La migration retire les lignes déjà
 * semées en base.
 */
class PublicBrandAssetsTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    /** Empreintes SHA-1 des visuels de marques tierces livrés par le socle. */
    private const INTERDITS = [
        '6be4dd7501f0189793c5841a48958978972313c1' => 'partner/1.png et 2.png (500px)',
        'cd9d9489736359f47e2dc61e3564ae4184eea1cc' => 'partner/atom.png (Atom)',
        '47d5931ae6e444394fd290e078b6fac08c2ee58e' => 'partner/digg.png (Digg)',
        'cb5befb60e59e40fad1fcb551feb44de4b4f796d' => 'partner/huawei.png (Huawei)',
        '40f71e4f554b01d7282375c92a06cffb134827a5' => 'partner/ups.png (UPS)',
        '845f31b6f5ed0dd49120238ecd192dd3ff1eb8e6' => 'images/default/we-courier-process.png (We Courier)',
        '05382e6b1aacf741fb63decf9213d288fd5d53dc' => 'images/default/logo.png (WeCourier SAAS)',
        'e53820feb4ef850ae5269928e9f87cb1a7d2987d' => 'images/default/light-logo.png (WeCourier SAAS)',
        'ab22f16c45a217b371cbd841283f3146509d74a7' => 'images/default/favicon.png (WeCourier SAAS)',
        '27517ad40b01642b0edab213bba55f1c6e6b0c2d' => 'frontend/logo.png (We Courier)',
        '0d2695e03873a14984624e28a5f1ddb6acd28349' => 'frontend/light-logo.png (We Courier)',
        '57d0bef568e056afdfd4ef34f4c413a5d77872a3' => 'frontend/favicon.png (We Courier)',
    ];

    private const SEMENCES_VITRINE = [SocialLinkSeeder::class, ServiceSeeder::class, WhyCourierSeeder::class, FaqSeeder::class, PartnerSeeder::class, BlogSeeder::class, PageSeeder::class, SectionSeeder::class];

    public function test_no_third_party_brand_image_is_served_from_public(): void
    {
        $images = Finder::create()->files()->in(public_path())
            ->exclude(['uploads', 'vendor'])
            ->name(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.svg', '*.webp', '*.ico']);

        $vus = 0;
        foreach ($images as $image) {
            $vus++;
            $empreinte = sha1_file($image->getPathname());
            $this->assertArrayNotHasKey($empreinte, self::INTERDITS, sprintf(
                '%s est %s : une marque tierce servie par le site public',
                $image->getRelativePathname(),
                self::INTERDITS[$empreinte] ?? ''
            ));
        }
        $this->assertGreaterThan(20, $vus, 'le parcours a bien lu les images de public/');

        $this->assertDirectoryDoesNotExist(public_path('frontend/images/partner'));
    }

    public function test_no_view_names_a_removed_brand_image(): void
    {
        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $vue) {
            $source = $vue->getContents();
            foreach (['frontend/images/partner/', 'we-courier-process', "'frontend/logo.png'", "'frontend/light-logo.png'", "'frontend/favicon.png'"] as $retire) {
                $this->assertStringNotContainsString($retire, $source, $vue->getRelativePathname());
            }
        }
    }

    public function test_the_default_logos_are_the_beninlink_ones_generated_from_their_source(): void
    {
        foreach (['logo.png' => [250, 40], 'light-logo.png' => [250, 40], 'favicon.png' => [80, 80]] as $fichier => [$largeur, $hauteur]) {
            $chemin = public_path('images/default/' . $fichier);
            $this->assertFileExists($chemin);
            [$l, $h] = getimagesize($chemin);
            $this->assertSame([$largeur, $hauteur], [$l, $h], "{$fichier} garde les dimensions que les gabarits attendent");
        }
        $this->assertStringContainsString("'Benin'", file_get_contents(resource_path('brand/generate.py')));
    }

    public function test_no_seeder_invents_a_partner(): void
    {
        $this->seedTenant();
        $avant = Partner::count();

        $this->seed(PartnerSeeder::class);
        (new CompanyFrontendDataSeeder)->companySiteData(2);

        $this->assertSame($avant, Partner::count());
        $this->assertSame(0, Partner::count(), 'aucune semence, dont UserSeeder pour la démo, ne pose de partenaire');
    }

    public function test_the_home_page_hides_the_section_until_real_partners_are_entered(): void
    {
        $this->seedTenant();
        $this->seed(self::SEMENCES_VITRINE);
        $this->mountTenantRoutes();

        $page = $this->get(self::HOTE . '/')->assertOk()->getContent();
        $this->assertStringNotContainsString('Nos partenaires', $page);
        $this->assertStringNotContainsString('partner-logo', $page);

        // Un vrai partenaire saisi au back-office (image envoyée sous uploads/partner/) : la section revient.
        $upload = Upload::create(['original' => 'uploads/partner/chambre.png']);
        Partner::forceCreate(['company_id' => 1, 'name' => 'Chambre de commerce du Bénin', 'image_id' => $upload->id, 'link' => '#', 'position' => 1]);

        $page = $this->get(self::HOTE . '/')->assertOk()->getContent();
        $this->assertStringContainsString('Nos partenaires', $page);
        $this->assertStringContainsString('alt="Chambre de commerce du Bénin"', $page);
    }

    public function test_the_migration_removes_the_seeded_brand_partners_and_keeps_real_ones(): void
    {
        $this->seedTenant();

        $socle = DB::table('uploads')->insertGetId(['original' => 'frontend/images/partner/huawei.png']);
        $json  = DB::table('uploads')->insertGetId(['original' => '{"original":"frontend\/images\/partner\/ups.png"}']);
        $brut  = DB::table('uploads')->insertGetId(['original' => '{"original":"frontend/images/partner/ups.png"}']);
        $vrai  = DB::table('uploads')->insertGetId(['original' => 'uploads/partner/chambre.png']);
        foreach ([$socle, $json, $brut, $vrai] as $position => $image) {
            DB::table('partners')->insert(['company_id' => 2, 'name' => "P{$position}", 'image_id' => $image, 'link' => '#', 'position' => $position]);
        }

        (require database_path('migrations/2026_10_08_100000_remove_socle_brand_partners.php'))->up();

        $this->assertSame([$vrai], DB::table('partners')->pluck('image_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(0, DB::table('uploads')->whereIn('id', [$socle, $json, $brut])->count(), 'les images du socle partent avec leurs lignes');
        $this->assertTrue(DB::table('uploads')->where('id', $vrai)->exists(), 'l\'image d\'un vrai partenaire reste');
    }
}
