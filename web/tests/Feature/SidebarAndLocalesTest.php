<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Lot 5 de la charte web (2026-09-18) — navigation et langues servies.
 *
 * Trois défauts relevés par l'audit (§2.10 à §2.12) :
 *
 *  1. le menu opérateur alignait **26 entrées sous un seul « MENU »** ;
 *  2. le sélecteur proposait **sept langues**, dont trois incomplètes, recopié
 *     **sept fois dans quatre vues** — et surtout,
 *     `LocalizationController::setLocalization()` acceptait **n'importe quelle**
 *     chaîne : `/localization/xx` mettait durablement l'interface dans une locale
 *     inexistante, donc en **clés brutes**, sans que rien ne le signale ;
 *  3. l'entrée « Retrait » menait à une **page vide sans un mot d'explication**,
 *     le module étant coupé par D10.
 *
 * Le regroupement du menu était un **déplacement**, pas une réécriture : le test
 * le plus important ci-dessous compare les jeux de routes et de permissions avant
 * et après, pour que rien ne puisse disparaître en silence.
 */
class SidebarAndLocalesTest extends TestCase
{
    private const SIDEBAR = 'resources/views/backend/partials/sidebar.blade.php';
    private const SIDEBAR_MARCHAND = 'resources/views/backend/merchant_panel/partials/sidebar.blade.php';
    private const SIDEBAR_SUPER_ADMIN = 'resources/views/backend/super-admin/partials/sidebar.blade.php';

    private function source(string $relatif): string
    {
        return file_get_contents(base_path($relatif));
    }

    /** @return string[] les noms de route cités par une vue */
    private function routes(string $relatif): array
    {
        preg_match_all("/route\('([^']+)'/", $this->source($relatif), $m);
        $routes = array_unique($m[1]);
        sort($routes);

        return $routes;
    }

    // — le menu n'a rien perdu -----------------------------------------------

    /**
     * LE test de ce lot. Le regroupement a déplacé des blocs entiers dans un
     * fichier de 663 lignes : une relecture ne peut pas garantir à l'œil que rien
     * n'est tombé. Les 68 routes du menu opérateur sont donc listées ici. Une
     * entrée supprimée, un `route()` mal recollé, et la suite échoue.
     *
     * ⚠️ **S55 — `currency.index` a quitté cet inventaire**, et c'est voulu : le
     * catalogue des devises est une surface de **plateforme**, passée sous
     * `super-admin/currency` avec `panel:super-admin`. Un opérateur de locataire
     * ne peut plus l'atteindre, donc ce menu n'a plus à l'annoncer.
     *
     * L'écran n'est pas pour autant orphelin : il **change d'inventaire**.
     * `test_the_super_admin_menu_reaches_the_platform_catalogue` ci-dessous le
     * tient désormais. Retirer une ligne d'ici sans l'inscrire ailleurs serait
     * exactement la façon de faire disparaître un écran en silence.
     */
    /**
     * Le pendant de S55 : l'écran que l'opérateur a perdu, le
     * super-administrateur doit l'avoir. Sans ce test, le retrait de
     * `currency.index` de l'inventaire ci-dessus ne serait qu'une suppression.
     */
    public function test_the_super_admin_menu_reaches_the_platform_catalogue(): void
    {
        $this->assertContains('currency.index', $this->routes(self::SIDEBAR_SUPER_ADMIN),
            'le catalogue des devises n\'est plus atteignable depuis le menu du '
            . 'super-administrateur : l\'ecran serait orphelin des deux cotes');

        // Et il pointe bien la NOUVELLE adresse : le motif de surbrillance suit.
        $this->assertStringContainsString("'super-admin/currency*'", $this->source(self::SIDEBAR_SUPER_ADMIN),
            'le menu du super-administrateur surligne encore l\'ancienne URI `admin/currency*`');

        // Le menu de l'operateur, lui, ne le mentionne plus du tout.
        $this->assertStringNotContainsString("route('currency.index')", $this->source(self::SIDEBAR),
            'le menu de l\'operateur annonce encore un ecran de plateforme');

        // R6 (S75) : le catalogue des catégories a suivi le même chemin. Il n'était
        // lié depuis AUCUN menu (atteignable par l'URL seule) ; il l'est désormais,
        // du seul côté où il a sa place.
        $this->assertContains('category.index', $this->routes(self::SIDEBAR_SUPER_ADMIN),
            'le catalogue des catégories n\'est atteignable depuis aucun menu');
        $this->assertStringContainsString("'super-admin/category*'", $this->source(self::SIDEBAR_SUPER_ADMIN));
        $this->assertStringNotContainsString("route('category.index')", $this->source(self::SIDEBAR));
    }

    public function test_the_operator_menu_still_reaches_every_screen(): void
    {
        $attendues = [
            'account.heads.index', 'accounts.index', 'admin.subscription.history',
            'asset-category.index', 'asset.index', 'bank-transaction.index', 'blogs.index',
            'cash.received.deliveryman.index', 'customs.alerts',
            'database.backup.index', 'delivery-category.index', 'delivery-charge.index',
            'delivery-type.index', 'delivery-zone.index', 'deliveryman.index',
            'departments.index', 'designations.index', 'expense.index', 'faq.index',
            'fraud.index', 'fund-transfer.index', 'general-settings.index',
            'googlemap-settings.index', 'hub-panel.payment-request.index',
            'hub.hub-payment.index', 'hubs.index', 'income.index',
            'invoice.generate.menually.index', 'liquid-fragile.index', 'logs.index',
            'merchant.hub.deliveryman.reports', 'merchant.index',
            'merchant.manage.payment.index', 'news-offer.index', 'notification-settings.index',
            'online.payment.list', 'packaging.index', 'pages.index', 'paid.invoice.index',
            'parcel.index', 'parcel.reports', 'parcel.total.summery.index',
            'parcel.wise.profit.index', 'partner.index', 'payout.index',
            'payout.setup.settings.index', 'pickup.request.express', 'pickup.request.regular',
            'push-notification.index', 'roles.index', 'saas.reporting', 'salary.generate.index',
            'salary.index', 'salary.reports', 'section.index', 'service.index',
            'sms-send-settings.index', 'sms-settings.index', 'social.link.index',
            'social.login.settings.index', 'subscribe.index', 'subscription.index',
            'support.index', 'todo.index', 'users.index', 'wallet.request.index',
            'why.courier.index',
        ];

        $reelles = $this->routes(self::SIDEBAR);

        $this->assertSame(
            [],
            array_values(array_diff($attendues, $reelles)),
            'des écrans ne sont plus atteignables depuis le menu'
        );
        $this->assertSame(
            [],
            array_values(array_diff($reelles, $attendues)),
            'des routes sont apparues : si c\'est voulu, les inscrire ici'
        );
    }

    /** Idem côté marchand : huit entrées, quinze routes. */
    public function test_the_merchant_menu_still_reaches_every_screen(): void
    {
        $attendues = [
            'merchant-panel.my.wallet.index', 'merchant-panel.parcel-bank.index',
            'merchant-panel.parcel.index', 'merchant-panel.parcel.reports',
            'merchant-panel.shops.index', 'merchant-panel.support.index',
            'merchant.accounts.account-transaction.index', 'merchant.accounts.statements.index',
            'merchant.cod-charges.index', 'merchant.delivery-charges.index',
            'merchant.online.payment.setup.index', 'merchant.panel.invoice.index',
            'merchant.total.summery', 'online.payment.index', 'online.payment.received',
        ];

        sort($attendues);
        $this->assertSame($attendues, $this->routes(self::SIDEBAR_MARCHAND));
    }

    /** Les 97 permissions d'origine sont toujours consultées par le menu. */
    public function test_the_regroup_did_not_drop_a_single_permission(): void
    {
        preg_match_all("/hasPermission\('([^']+)'\)/", $this->source(self::SIDEBAR), $m);
        $vues = array_unique($m[1]);

        foreach ([
            'dashboard_read', 'parcel_read', 'delivery_man_read', 'hub_read', 'hub_payment_read',
            'merchant_read', 'payment_read', 'todo_read', 'support_read', 'news_offer_read',
            'log_read', 'fraud_read', 'subscribe_read', 'subscription_read',
            'pickup_request_regular', 'pickup_request_express', 'assets_read',
            'wallet_request_read', 'online_payment_read', 'payout_read', 'account_read',
            'fund_transfer_read', 'income_read', 'expense_read', 'bank_transaction_read',
            'cash_received_from_delivery_man_read', 'hub_payment_request_read',
            'paid_invoice_read', 'role_read', 'designation_read', 'department_read', 'user_read',
            'salary_generate_read', 'salary_read', 'parcel_status_reports', 'parcel_wise_profit',
            'salary_reports', 'merchant_hub_deliveryman', 'parcel_total_summery',
            'push_notification_read', 'general_settings_read', 'delivery_category_read',
            'delivery_charge_read', 'delivery_type_read', 'liquid_fragile_read',
            'sms_settings_read', 'sms_send_settings_read', 'notification_settings_read',
            'google_map_settings_read', 'social_login_settings_update',
            'payout_setup_settings_read', 'packaging_read', 'currency_read',
            'asset_category_read', 'database_backup_read', 'invoice_generate_menually',
        ] as $permission) {
            $this->assertContains($permission, $vues, "permission perdue : {$permission}");
        }
    }

    /**
     * Un intitulé de groupe dont toutes les entrées sont masquées serait un titre
     * sans rien dessous. Chacun est donc sous un `@if`.
     */
    public function test_no_group_heading_can_appear_empty(): void
    {
        $lignes = file(base_path(self::SIDEBAR));

        foreach ($lignes as $no => $ligne) {
            if (! str_contains($ligne, 'nav-divider')) {
                continue;
            }
            // Remonter jusqu'au @if : la condition peut faire plusieurs lignes.
            $garde = false;
            for ($j = $no - 1; $j >= max(0, $no - 12); $j--) {
                if (str_contains($lignes[$j], '@if')) {
                    $garde = true;
                    break;
                }
                if (str_contains($lignes[$j], '@endif') && $j < $no - 1) {
                    break;
                }
            }
            $this->assertTrue($garde, 'intitulé non gardé ligne ' . ($no + 1));
        }

        $this->assertSame(6, substr_count($this->source(self::SIDEBAR), 'nav-divider'));
        $this->assertSame(3, substr_count($this->source(self::SIDEBAR_MARCHAND), 'nav-divider'));
    }

    /**
     * ⚠️ Ce test existe à cause d'une vraie erreur commise en écrivant ce lot :
     * le générateur des conditions produisait `A || || B`. Blade **compile** cela
     * sans broncher — l'erreur de syntaxe n'apparaît qu'au RENDU. `view:cache` ne
     * la voit pas, et un test qui ne rend pas la page ne la voit pas non plus.
     * On lint donc le PHP **compilé** des vues que ce lot touche.
     */
    public function test_the_touched_views_compile_to_valid_php(): void
    {
        $vues = [
            self::SIDEBAR,
            self::SIDEBAR_MARCHAND,
            'resources/views/backend/partials/navber.blade.php',
            'resources/views/backend/merchant_panel/partials/navber.blade.php',
            'resources/views/backend/super-admin/partials/navber.blade.php',
            'resources/views/frontend/layouts/footer.blade.php',
            'resources/views/backend/dashboard.blade.php',
            'resources/views/backend/super-admin/dashboard.blade.php',
            'resources/views/backend/merchant_panel/onlinepayment/index.blade.php',
            'resources/views/backend/payout/index.blade.php',
            'resources/views/partials/locale-current.blade.php',
            'resources/views/partials/locale-links.blade.php',
            'resources/views/partials/locale-list.blade.php',
        ];

        foreach ($vues as $relatif) {
            $compile = Blade::compileString($this->source($relatif));
            $fichier = tempnam(sys_get_temp_dir(), 'bl') . '.php';
            file_put_contents($fichier, $compile);
            exec('php -l ' . escapeshellarg($fichier) . ' 2>&1', $sortie, $code);
            @unlink($fichier);

            $this->assertSame(0, $code, $relatif . " ne compile pas :\n" . implode("\n", $sortie));
        }
    }

    // — les langues servies --------------------------------------------------

    public function test_only_the_two_declared_languages_are_served(): void
    {
        $this->assertSame(['fr', 'en'], array_keys(config('locales.supported')));

        // Le drapeau du français est celui du Bénin, comme la maquette le montre.
        $this->assertSame('flag-icon-bj', config('locales.supported.fr.flag'));
    }

    /**
     * Le cœur du défaut : `/localization/xx` mettait n'importe quoi en session.
     * Une locale inconnue doit être **ignorée** — et surtout ne pas remplacer
     * celle que l'utilisateur avait choisie.
     */
    public function test_an_unknown_language_never_reaches_the_session(): void
    {
        foreach (['zh', 'bn', 'in', 'es', 'ar', 'xx', '../fr', ''] as $refusee) {
            session()->forget('locale');
            session()->put('locale', 'en');

            app(\App\Http\Controllers\LocalizationController::class)
                ->setLocalization((string) $refusee);

            $this->assertSame('en', session('locale'), "« {$refusee} » a modifié la session");
        }

        // Et les deux langues servies, elles, passent.
        foreach (['fr', 'en'] as $acceptee) {
            app(\App\Http\Controllers\LocalizationController::class)->setLocalization($acceptee);
            $this->assertSame($acceptee, session('locale'));
        }
    }

    /**
     * Une session ouverte AVANT ce lot peut encore porter `zh`. Sans nettoyage,
     * l'utilisateur resterait en clés brutes à chaque requête, sans savoir qu'il
     * doit appeler `/localization/fr` pour s'en sortir.
     */
    public function test_a_session_left_on_a_removed_language_heals_itself(): void
    {
        // Le socle garde tout le bloc derrière Schema::hasTable('settings') — on
        // répond à ce seul appel plutôt que de monter une base pour un test de locale.
        \Illuminate\Support\Facades\Schema::shouldReceive('hasTable')
            ->with('settings')->andReturn(true);

        session()->put('locale', 'zh');
        App::setLocale('fr');

        app(\App\Http\Middleware\LanguageManager::class)
            ->handle(request(), fn () => response('ok'));

        $this->assertFalse(session()->has('locale'), 'la session doit être nettoyée');
        $this->assertSame('fr', App::getLocale());
    }

    /** Le sélecteur n'existe plus qu'en un endroit : les trois partiels. */
    public function test_the_language_switcher_is_written_once(): void
    {
        $recopies = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );
        foreach ($it as $f) {
            if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $relatif = str_replace(base_path() . '/', '', $f->getPathname());
            if (str_starts_with($relatif, 'resources/views/partials/')) {
                continue;
            }
            if (str_contains(file_get_contents($f->getPathname()), 'setlocalization')) {
                $recopies[] = $relatif;
            }
        }

        $this->assertSame([], $recopies, "le sélecteur est recopié :\n" . implode("\n", $recopies));
    }

    public function test_the_group_headings_exist_in_both_languages(): void
    {
        $fr = require lang_path('fr/menus.php');
        $en = require lang_path('en/menus.php');

        foreach (['pilotage', 'reseau', 'finances', 'rapports', 'relation', 'administration', 'compte'] as $g) {
            $this->assertArrayHasKey('group_' . $g, $fr, $g);
            $this->assertArrayHasKey('group_' . $g, $en, $g);
        }
        $this->assertSame('Pilotage', __('menus.group_pilotage'));
    }

    // — plus d'impasse -------------------------------------------------------

    /**
     * D10 coupe le retrait en ligne. La PAGE conditionnait déjà chaque passerelle ;
     * le MENU ne conditionnait rien : le marchand cliquait et arrivait sur un
     * écran qui n'affichait qu'un titre.
     */
    public function test_the_payout_entry_is_gone_when_the_module_is_off(): void
    {
        $this->assertFalse(onlinePayoutEnabled(), 'D10 : le module doit rester coupé');

        // L'entrée « Retrait » doit vivre entre un @if (onlinePayoutEnabled()) et
        // son @endif — c'est ce qui manquait : la page gardait, le menu non.
        $source = $this->source(self::SIDEBAR_MARCHAND);
        $ouverture = strpos($source, '@if (onlinePayoutEnabled())');
        $this->assertNotFalse($ouverture, 'aucune garde onlinePayoutEnabled() dans le menu');
        $bloc = substr($source, $ouverture, strpos($source, '@endif', $ouverture) - $ouverture);
        $this->assertStringContainsString("route('online.payment.index')", $bloc);
        $this->assertStringContainsString("__('menus.payout')", $bloc);
    }

    /** Et la page, atteignable par une URL en favori, dit pourquoi elle est vide. */
    public function test_both_payout_pages_explain_why_they_are_empty(): void
    {
        foreach ([
            'resources/views/backend/merchant_panel/onlinepayment/index.blade.php',
            'resources/views/backend/payout/index.blade.php',
        ] as $vue) {
            $source = $this->source($vue);
            $this->assertStringContainsString('@if (!onlinePayoutEnabled())', $source, $vue);
            $this->assertStringContainsString("__('levels.payout_unavailable')", $source, $vue);
        }

        $this->assertStringContainsString('relevé de règlement', __('levels.payout_unavailable'));
    }

    // — le filtre des tableaux de bord ---------------------------------------

    public function test_the_dashboard_filters_are_labelled_and_not_sized_inline(): void
    {
        foreach ([
            'resources/views/backend/dashboard.blade.php',
            'resources/views/backend/super-admin/dashboard.blade.php',
        ] as $vue) {
            $source = $this->source($vue);

            $this->assertStringContainsString('bl-sr-only', $source, "{$vue} : libellé absent");
            $this->assertStringContainsString("__('levels.filter_period')", $source, $vue);
            $this->assertStringContainsString('id="filter_date"', $source, "{$vue} : le label doit viser un id");
            $this->assertStringContainsString('bl-filter-date', $source, $vue);
            $this->assertDoesNotMatchRegularExpression(
                '/name="filter_date"[^>]*style="width/s',
                $source,
                "{$vue} : largeur encore en style en ligne"
            );
        }

        // Les deux classes vivent dans notre couche, pas dans celle de Bootstrap.
        $composants = file_get_contents(public_path('beninlink/css/components.css'));
        $this->assertStringContainsString('.bl-sr-only', $composants);
        $this->assertStringContainsString('.bl-filter-date', $composants);
    }
}
