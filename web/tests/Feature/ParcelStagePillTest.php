<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Services\Parcel\MerchantStage;
use ReflectionClass;
use Tests\TestCase;

/**
 * Lot 2 de la charte web : la pastille de statut.
 *
 * Deux choses sont tenues ici, et la première est la plus importante :
 *
 * 1. **La table PHP ne peut pas diverger de celle de l'app.** `MerchantStage`
 *    est un port de `mobile/src/domain/parcelStatus.ts` ; si l'un des deux
 *    bouge sans l'autre, le même colis se lit différemment selon le support.
 *    Ce test relit le TypeScript et compare les 33 correspondances.
 * 2. **La sémantique de la charte** : le rouge est un incident, le vert veut
 *    dire livré, et aucun code ne rend une cellule vide.
 */
class ParcelStagePillTest extends TestCase
{
    /** Les correspondances écrites dans le fichier de l'app, lues à la source. */
    private function tableDeLApp(): array
    {
        $ts = file_get_contents(base_path('../mobile/src/domain/parcelStatus.ts'));
        $this->assertNotFalse($ts, 'mobile/src/domain/parcelStatus.ts est introuvable');

        // Les constantes numériques déclarées par l'app, qu'on vérifie aussi.
        preg_match_all('/^\s{2}([A-Z_]+):\s*(\d+),/m', $ts, $c, PREG_SET_ORDER);
        $codes = [];
        foreach ($c as $m) {
            $codes[$m[1]] = (int) $m[2];
        }

        // Puis la table 33 → 7 : [B.NOM]: 'etape',
        preg_match_all("/\[B\.([A-Z_]+)\]:\s*'([a-z_]+)'/", $ts, $t, PREG_SET_ORDER);
        $table = [];
        foreach ($t as $m) {
            $this->assertArrayHasKey($m[1], $codes, "l'app référence {$m[1]} sans le déclarer");
            $table[$codes[$m[1]]] = $m[2];
        }

        return [$codes, $table];
    }

    public function test_the_php_table_is_the_same_as_the_app_one(): void
    {
        [$codes, $attendu] = $this->tableDeLApp();

        $this->assertCount(33, $attendu, "l'app doit ranger les 33 codes");
        $this->assertSame($attendu, MerchantStage::table(), 'la table PHP a divergé de celle de l’app');
    }

    /** Les valeurs numériques appartiennent au contrat : l'app ne les réinvente pas. */
    public function test_the_app_uses_the_backend_numbers(): void
    {
        [$codes] = $this->tableDeLApp();
        $socle = (new ReflectionClass(ParcelStatus::class))->getConstants();

        foreach ($codes as $nom => $valeur) {
            $this->assertArrayHasKey($nom, $socle, "{$nom} n'existe pas dans ParcelStatus");
            $this->assertSame($socle[$nom], $valeur, "{$nom} ne vaut pas la même chose des deux côtés");
        }
    }

    /** Aucun des 33 codes ne peut rendre une cellule vide — c'était le §2.6. */
    public function test_every_backend_status_gets_a_pill(): void
    {
        $familles = ['wait', 'transit', 'hub', 'assign', 'done', 'return', 'partial'];

        foreach ((new ReflectionClass(ParcelStatus::class))->getConstants() as $nom => $code) {
            if (!is_int($code)) {
                continue;
            }
            $this->assertContains(MerchantStage::pill($code), $familles, "le statut {$nom} n'a pas de pastille");
        }
    }

    /** Et un code hors contrat non plus : le repli est celui de l'app. */
    public function test_an_unknown_code_falls_back_instead_of_rendering_nothing(): void
    {
        $this->assertSame(MerchantStage::PENDING, MerchantStage::for(999));
        $this->assertSame('wait', MerchantStage::pill(999));

        $rendu = StatusParcel(999);
        $this->assertStringContainsString('bl-pill--wait', $rendu);
        $this->assertNotSame('', trim(strip_tags($rendu)), 'la pastille ne doit jamais être vide');
    }

    /**
     * La sémantique que le lot 2 vient corriger — trois lectures que la charte
     * interdisait et que le socle produisait.
     */
    public function test_the_charter_semantics_replace_the_socle_ones(): void
    {
        // « en attente » était ROUGE ; le rouge est réservé à l'incident.
        $this->assertSame('wait', MerchantStage::pill(ParcelStatus::PENDING));

        // « reçu par le ramasseur » était VERT ; le vert veut dire livré.
        $this->assertSame('transit', MerchantStage::pill(ParcelStatus::RECEIVED_BY_PICKUP_MAN));

        // La livraison partielle était VERTE ; c'est un incident → avertissement.
        $this->assertSame('partial', MerchantStage::pill(ParcelStatus::PARTIAL_DELIVERED));
        $this->assertTrue(MerchantStage::isIncident(ParcelStatus::PARTIAL_DELIVERED));

        // Les retours se répartissaient entre dark / info / success : une famille.
        foreach ([
            ParcelStatus::RETURN_WAREHOUSE, ParcelStatus::ASSIGN_MERCHANT,
            ParcelStatus::RETURNED_MERCHANT, ParcelStatus::RETURN_TO_COURIER,
            ParcelStatus::RETURN_ASSIGN_TO_MERCHANT, ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ] as $code) {
            $this->assertSame('return', MerchantStage::pill($code));
        }

        // Et seul « livré » porte le vert.
        $this->assertSame('done', MerchantStage::pill(ParcelStatus::DELIVERED));
        $this->assertSame('done', MerchantStage::pill(ParcelStatus::DELIVER));
    }

    /** Plus de vocabulaire Bootstrap 4 dans le rendu. */
    public function test_the_pill_no_longer_speaks_bootstrap(): void
    {
        $rendu = StatusParcel(ParcelStatus::DELIVERED);

        $this->assertStringContainsString('bl-pill bl-pill--done', $rendu);
        $this->assertStringNotContainsString('badge', $rendu);
    }

    /**
     * Les vues d'IMPRESSION ne passent pas par le layout du back-office. Sans
     * la feuille de la charte, la bascule vers `bl-pill` les aurait laissées en
     * texte nu — alors qu'elles stylaient les `badge-*` du socle.
     */
    public function test_the_print_views_load_the_charter_too(): void
    {
        foreach ([
            'backend/reports/parcel_reports_print',
            'backend/merchant_panel/reports/parcel_reports_print',
            'backend/parcel/bulk_print',
        ] as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue . '.blade.php'));
            $this->assertStringContainsString('beninlink/css/tokens.css', $source, $vue);
            $this->assertStringContainsString('beninlink/css/theme-print.css', $source, $vue);
        }

        $feuille = file_get_contents(public_path('beninlink/css/theme-print.css'));
        foreach (['wait', 'transit', 'hub', 'assign', 'done', 'return', 'partial'] as $famille) {
            $this->assertStringContainsString(".bl-pill--{$famille}", $feuille);
        }

        // Sans cela les fonds ne s'impriment pas, et les familles deviennent
        // indiscernables : c'est la raison d'être de la feuille.
        $this->assertStringContainsString('print-color-adjust: exact', $feuille);
    }
}
