<?php

namespace Tests\Feature;

use App\Models\Backend\FrontWeb\Blog;
use Database\Seeders\Backend\FrontWeb\BlogSeeder;
use Database\Seeders\Backend\FrontWeb\PageSeeder;
use Database\Seeders\Backend\FrontWeb\SectionSeeder;
use Database\Seeders\Backend\FrontWeb\SocialLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S122** — une date affichée parle la langue de son lecteur, sur 24 heures.
 *
 * `->format()` et `date()` ne se traduisent pas : le socle affichait « 08 Oct 2026 06:35:00 pm »
 * au back-office, « 08th october 2026 » par `dateFormat()` (aussi dans l'API : colis, relevés,
 * douane) et « 01st january 1970 » pour une date absente. Une date se met en forme par
 * `dateFormat()` / `dateTimeFormat()` (Carbon `translatedFormat`, qui suit la locale — donc
 * l'`Accept-Language` négocié par l'API, S90), ou par `translatedFormat` directement.
 */
class DatesSpeakFrenchTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    /** Lettres d'un format PHP qui écrivent un mot anglais (jour, mois, suffixe) ou une heure sur 12. */
    private const ANGLAISES = '/[DlNSFMaAhg]/';

    public function test_the_date_helpers_follow_the_language(): void
    {
        app()->setLocale('fr');
        $this->assertSame('17 août 2026', dateFormat('2026-08-17 21:29:00'));
        $this->assertSame('17 août 2026, 21:29', dateTimeFormat('2026-08-17 21:29:00'));
        $this->assertSame('8 octobre 2026', dateFormat('08-10-2026'), 'invoices.invoice_date est une chaîne d-m-Y');

        app()->setLocale('en');
        $this->assertSame('17 August 2026', dateFormat('2026-08-17 21:29:00'));
        $this->assertSame('17 Aug 2026, 21:29', dateTimeFormat('2026-08-17 21:29:00'));

        foreach ([null, ''] as $absente) {
            $this->assertNull(dateFormat($absente), 'une date absente ne devient pas le 1er janvier 1970');
            $this->assertNull(dateTimeFormat($absente));
        }
    }

    /** Aucune vue servie ni ressource d'API ne met une date en forme avec un mot anglais ou une heure sur 12. */
    public function test_no_view_or_resource_formats_a_date_in_english(): void
    {
        $fichiers = Finder::create()->files()->in([resource_path('views'), app_path('Http/Resources'), app_path('Exports')])
            ->name('*.php')->notPath('installer')->sortByName();

        $fautifs = [];
        $lus = 0;
        foreach ($fichiers as $fichier) {
            $lus++;
            preg_match_all('/(?:->format|\bdate)\(\s*([\'"])(.*?)\1/', $fichier->getContents(), $appels, PREG_SET_ORDER);
            foreach ($appels as [$appel, , $format]) {
                if (preg_match(self::ANGLAISES, preg_replace('/\\\\./', '', $format))) {
                    $fautifs[] = str_replace(base_path() . '/', '', $fichier->getPathname()) . " : {$appel}";
                }
            }
        }

        $this->assertGreaterThan(300, $lus, 'le parcours a bien lu les vues et les ressources');
        $this->assertSame([], $fautifs, "date en forme anglaise :\n" . implode("\n", $fautifs));
    }

    /** Ce qu'un visiteur lit : la date d'un article sur la page d'accueil, en français. */
    public function test_the_public_home_page_dates_its_articles_in_french(): void
    {
        $this->seedTenant();
        $this->seed([SocialLinkSeeder::class, BlogSeeder::class, PageSeeder::class, SectionSeeder::class]);
        Blog::query()->update(['updated_at' => '2026-08-17 21:29:00']);
        $this->mountTenantRoutes();
        app()->setLocale('fr');

        $page = $this->get(self::HOTE . '/')->assertOk()->getContent();

        $this->assertStringContainsString('17 août 2026', $page);
        $this->assertStringNotContainsString('17 Aug 2026', $page);
    }
}
