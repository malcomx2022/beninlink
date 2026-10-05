<?php

namespace Tests\Feature;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Enums\InvoiceStatus;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Wallet;
use App\Notifications\MerchantNotification;
use App\Services\OpenApi\SpecGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S78 (T8)** — une liste paginée dit où elle finit.
 *
 * Jusqu'ici la taille de page était un **contrat implicite** : le paginateur,
 * imbriqué dans `data`, perdait ses compteurs à la sérialisation, et l'app
 * déduisait « il en reste » d'une page pleine (`MerchantAppCustomsContractTest`
 * compare encore les constantes de l'app aux `paginate(n)` du serveur — c'est le
 * filet pour une taille qui bouge d'un seul côté). Depuis S78,
 * `ApiReturnFormatTrait::responseWithPage()` ajoute un bloc `page` à la
 * **racine** de l'enveloppe : `current`, `per_page`, `last`, `total`.
 *
 * Onzième filet, même forme que les autres : il **énumère** ce qu'il surveille.
 * Toute méthode d'un contrôleur d'API qui pagine — directement, ou par la
 * méthode de dépôt qu'elle appelle, elle-même suivie d'un niveau — doit répondre
 * par `responseWithPage()`, être dans la liste ci-dessous, et son opération
 * OpenAPI doit référencer le schéma `Page`. Puis le comportement : les quatre
 * routes de l'app marchand, pages 1 et 2, et une liste vide.
 */
class ApiPaginationContractTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    /**
     * Les méthodes d'API qui paginent. Ajouter une route paginée, c'est l'ajouter
     * ici ET la faire répondre par `responseWithPage()` ET documenter `Page`.
     */
    private const PAGINEES = [
        'AccountTransactionController@filter',
        'CustomsController@alerts',
        // Les quatre suivantes paginent par leur DÉPÔT, partagé avec les tables du
        // back-office : l'API servait dix lignes sans le dire. Trouvées par ce filet.
        'FraudController@index',
        'HubController@index',
        'InvoiceController@invoiceLists',
        'NotificationController@index',
        'ShopsController@index',
        'SupportController@index',
        'WalletController@history',
    ];

    private const CONTROLEURS = 'app/Http/Controllers/Api/V10';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $this->merchant = Merchant::firstOrFail();
    }

    // ---- lecture de source -----------------------------------------------------------

    /** @return array<string, string> nom de méthode => corps */
    private function methodes(string $source): array
    {
        preg_match_all('/function\s+(\w+)\s*\(/', $source, $m, PREG_OFFSET_CAPTURE);
        $methodes = [];
        foreach ($m[1] as $i => [$nom, $pos]) {
            $fin = $m[1][$i + 1][1] ?? strlen($source);
            $methodes[$nom] = substr($source, $pos, $fin - $pos);
        }

        return $methodes;
    }

    /** Les classes que le constructeur du contrôleur reçoit, par nom de propriété. */
    private function dependances(string $source): array
    {
        if (!preg_match('/function\s+__construct\s*\((.*?)\)/s', $source, $m)) {
            return [];
        }
        preg_match_all('/([\w\\\\]+)\s+\$(\w+)/', $m[1], $params, PREG_SET_ORDER);
        preg_match_all('/^use\s+([\w\\\\]+);/m', $source, $uses);
        $resolu = [];
        foreach ($params as [, $type, $nom]) {
            $court = ltrim($type, '\\');
            foreach ($uses[1] as $use) {
                if (str_ends_with($use, '\\' . $court)) {
                    $court = $use;
                    break;
                }
            }
            $resolu[$nom] = $court;
        }

        return $resolu;
    }

    /** Source du dépôt qui implémente une interface (ou la classe elle-même). */
    private function sourceDuDepot(string $classe): ?string
    {
        $base = class_basename($classe);
        foreach (glob(base_path('app/Repositories/*/*.php')) as $fichier) {
            $src = file_get_contents($fichier);
            if (preg_match('/^class\s+\w+(?:\s+extends\s+\w+)?\s+implements\s+[^{]*\b' . preg_quote($base, '/') . '\b/m', $src)
                || preg_match('/^class\s+' . preg_quote($base, '/') . '\b/m', $src)) {
                return $src;
            }
        }

        return null;
    }

    /** Une méthode de dépôt pagine-t-elle, directement ou par une méthode sœur qu'elle appelle ? */
    private function depotPagine(string $source, string $methode, int $profondeur = 0): bool
    {
        $corps = $this->methodes($source)[$methode] ?? '';
        if (str_contains($corps, '->paginate(')) {
            return true;
        }
        if ($profondeur >= 1) {
            return false;
        }
        preg_match_all('/\$this->(\w+)\(/', $corps, $appels);
        foreach (array_unique($appels[1]) as $soeur) {
            if ($soeur !== $methode && $this->depotPagine($source, $soeur, $profondeur + 1)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string> "Controleur@methode" => corps, pour toute méthode qui pagine */
    private function methodesQuiPaginent(): array
    {
        $trouvees = [];
        foreach (glob(base_path(self::CONTROLEURS . '/*.php')) as $fichier) {
            $source = file_get_contents($fichier);
            $controleur = basename($fichier, '.php');
            $dependances = $this->dependances($source);

            foreach ($this->methodes($source) as $nom => $corps) {
                if ($nom === '__construct') {
                    continue;
                }
                $pagine = str_contains($corps, '->paginate(');

                preg_match_all('/\$this->(\w+)->(\w+)\(/', $corps, $appels, PREG_SET_ORDER);
                foreach ($appels as [, $propriete, $methode]) {
                    if (!$pagine && isset($dependances[$propriete])) {
                        $depot = $this->sourceDuDepot($dependances[$propriete]);
                        $pagine = $depot !== null && $this->depotPagine($depot, $methode);
                    }
                }

                if ($pagine) {
                    $trouvees["{$controleur}@{$nom}"] = $corps;
                }
            }
        }
        ksort($trouvees);

        return $trouvees;
    }

    // ---- le filet ------------------------------------------------------------------------

    public function test_la_liste_des_methodes_qui_paginent_est_celle_attendue(): void
    {
        $this->assertSame(self::PAGINEES, array_keys($this->methodesQuiPaginent()),
            'une méthode d\'API pagine sans être inscrite ici (ou l\'inverse) : ajouter la route à PAGINEES, '
            . 'la faire répondre par responseWithPage() et documenter `Page` dans l\'overlay');
    }

    public function test_toute_methode_qui_pagine_repond_par_response_with_page(): void
    {
        foreach ($this->methodesQuiPaginent() as $nom => $corps) {
            $this->assertStringContainsString('responseWithPage(', $corps,
                "{$nom} pagine mais ne dit pas où finit la liste : répondre par responseWithPage()");
            $this->assertStringNotContainsString('responseWithSuccess(', $corps,
                "{$nom} : une réponse paginée ne passe pas par responseWithSuccess()");
        }
    }

    /** Chaque opération paginée documente `page` : l'overlay la décrit par `$paged`, et la spec publiée le porte. */
    public function test_toute_route_qui_pagine_documente_le_bloc_page_dans_l_openapi(): void
    {
        $generateur = new SpecGenerator();
        $spec = $generateur->generate();
        $publiee = json_decode(file_get_contents(public_path('openapi/v10.json')), true);

        $this->assertArrayHasKey('Page', $spec['components']['schemas'], 'le schéma Page (S78)');
        $this->assertSame(['current', 'per_page', 'last', 'total'], array_keys($spec['components']['schemas']['Page']['properties']));
        $this->assertArrayHasKey('Page', $publiee['components']['schemas'], 'la spec publiée est à jour (php artisan openapi:generate)');

        $verifiees = 0;
        foreach (Route::getRoutes() as $route) {
            $nomComplet = (string) $route->getActionName();
            // Le back-office a ses propres SupportController@index et consorts : seule l'API compte.
            if (!str_starts_with($nomComplet, 'App\\Http\\Controllers\\Api\\V10\\')) {
                continue;
            }
            $action = class_basename($nomComplet);
            if (!in_array($action, self::PAGINEES, true)) {
                continue;
            }
            $chemin = '/' . $generateur->relativePath($route);
            foreach ($route->methods() as $verbe) {
                if ($verbe === 'HEAD') {
                    continue;
                }
                foreach (['générée' => $spec, 'publiée' => $publiee] as $quoi => $document) {
                    $reponse = json_encode($document['paths'][$chemin][strtolower($verbe)]['responses']['200'] ?? [], JSON_UNESCAPED_SLASHES);
                    $this->assertStringContainsString('#/components/schemas/Page', $reponse,
                        "{$verbe} {$chemin} ({$action}) pagine : sa réponse 200 doit référencer Page dans la spec {$quoi}");
                }
                $verifiees++;
            }
        }
        $this->assertSame(count(self::PAGINEES), $verifiees, 'chaque méthode paginée a une route');
    }

    // ---- le comportement ---------------------------------------------------------------

    private function entetes(): array
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        return ['apiKey' => self::API_KEY];
    }

    private function assertPage(array $attendu, \Illuminate\Testing\TestResponse $reponse): void
    {
        $reponse->assertOk()->assertJsonPath('success', true);
        $this->assertSame($attendu, $reponse->json('page'), 'le bloc page, à la racine de l\'enveloppe');
    }

    public function test_les_alertes_douanieres_disent_ou_finit_la_liste(): void
    {
        for ($i = 0; $i < 25; $i++) {
            CustomsAlert::create([
                'company_id' => $this->merchant->company_id,
                'merchant_id' => $this->merchant->id,
                'country_code' => 'TG',
                'country_name' => 'Togo',
                'goods_category' => 'textile',
                'level' => CustomsLevel::WARNING,
                'required_document' => 'Déclaration',
                'message' => "Alerte {$i}",
                'status' => CustomsAlertStatus::PENDING,
            ]);
        }

        $page1 = $this->getJson('/api/v10/customs/alerts', $this->entetes());
        $page1->assertJsonCount(20, 'data.alerts');
        $this->assertPage(['current' => 1, 'per_page' => 20, 'last' => 2, 'total' => 25], $page1);

        $page2 = $this->getJson('/api/v10/customs/alerts?page=2', $this->entetes());
        $page2->assertJsonCount(5, 'data.alerts');
        $this->assertPage(['current' => 2, 'per_page' => 20, 'last' => 2, 'total' => 25], $page2);
    }

    /** Une liste vide : `last` vaut 1, pas 0 — `current < last` est faux, l'app ne redemande rien. */
    public function test_une_liste_vide_est_une_derniere_page(): void
    {
        $this->assertPage(['current' => 1, 'per_page' => 20, 'last' => 1, 'total' => 0],
            $this->getJson('/api/v10/customs/alerts', $this->entetes()));
    }

    public function test_le_fil_de_notifications_dit_ou_finit_la_liste(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->merchant->user->notify(new MerchantNotification(MerchantNotification::KIND_MESSAGE, "Titre {$i}", 'corps'));
        }

        $page1 = $this->getJson('/api/v10/notifications/index', $this->entetes());
        $page1->assertJsonCount(20, 'data.notifications')->assertJsonPath('data.unread_count', 25);
        $this->assertPage(['current' => 1, 'per_page' => 20, 'last' => 2, 'total' => 25], $page1);

        $this->getJson('/api/v10/notifications/index?page=2', $this->entetes())->assertJsonCount(5, 'data.notifications');
    }

    public function test_l_historique_du_portefeuille_dit_ou_finit_la_liste(): void
    {
        for ($i = 0; $i < 12; $i++) {
            (new Wallet())->forceFill([
                'company_id' => $this->merchant->company_id,
                'source' => 'test',
                'user_id' => $this->merchant->user_id,
                'merchant_id' => $this->merchant->id,
                'transaction_id' => "RECH-{$i}",
                'amount' => 1000 + $i,
                'type' => WalletType::INCOME,
                'payment_method' => WalletPaymentMethod::OFFLINE,
                'status' => WalletStatus::APPROVED,
            ])->save();
        }

        $page1 = $this->getJson('/api/v10/wallet/history', $this->entetes());
        $page1->assertJsonCount(10, 'data.entries');
        $this->assertPage(['current' => 1, 'per_page' => 10, 'last' => 2, 'total' => 12], $page1);

        $this->getJson('/api/v10/wallet/history?page=2', $this->entetes())->assertJsonCount(2, 'data.entries');
    }

    /**
     * Les relevés : seule route de l'app qui renvoyait le paginateur **nu**. Elle
     * rejoint l'enveloppe ; `data` reste le tableau (l'app installée ne lit que lui).
     */
    public function test_la_liste_des_releves_rejoint_l_enveloppe_et_garde_data_en_tableau(): void
    {
        for ($i = 0; $i < 12; $i++) {
            Invoice::forceCreate([
                'company_id' => $this->merchant->company_id,
                'merchant_id' => $this->merchant->id,
                'invoice_id' => sprintf('INV-%02d', $i),
                'invoice_date' => now()->toDateString(),
                'total_charge' => 1500,
                'cash_collection' => 50000,
                'current_payable' => 48500,
                'parcels_id' => [],
                'status' => InvoiceStatus::UNPAID,
            ]);
        }

        $page1 = $this->getJson('/api/v10/invoice-list/index', $this->entetes());
        $this->assertPage(['current' => 1, 'per_page' => 10, 'last' => 2, 'total' => 12], $page1);
        $this->assertCount(10, $page1->json('data'), '`data` est le tableau des relevés, pas un objet');
        $this->assertArrayHasKey('invoice_id', $page1->json('data.0'));
        $this->assertNull($page1->json('meta'), 'plus de paginateur nu : `meta` a laissé place à `page`');

        $this->assertCount(2, $this->getJson('/api/v10/invoice-list/index?page=2', $this->entetes())->json('data'));
    }
}
