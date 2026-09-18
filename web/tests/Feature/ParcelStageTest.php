<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Services\Parcel\ParcelStage;
use Tests\TestCase;

/**
 * Lot 2 de la charte web (2026-09-18) — la sémantique des statuts colis.
 *
 * Le socle peignait les 33 statuts selon une table qui contredisait la charte :
 * « en attente » en **rouge** alors que le rouge est réservé à l'incident,
 * « reçu par le ramasseur » en **vert** alors que le vert veut dire livré,
 * « livraison partielle » en **vert** alors que c'est un incident — sur un
 * relevé d'argent. Et cette table existait en **trois copies** (`StatusParcel()`,
 * `Parcel::getParcelStatusAttribute()`, `Parcel::getStatusParcelAttribute()`),
 * chacune couvrant 19 codes sur 33, sans `else` ni variable initialisée.
 *
 * Tout passe désormais par `ParcelStage`, portage de
 * `mobile/src/domain/parcelStatus.ts`. Ces tests fixent les deux choses qui
 * comptent : que la table du web et celle de l'app restent d'accord, et que la
 * charte soit respectée statut par statut.
 */
class ParcelStageTest extends TestCase
{
    /** Étape de l'app marchand => famille de pastille côté web. */
    private const CORRESPONDANCE = [
        'pending' => ParcelStage::WAIT,
        'pickup_assigned' => ParcelStage::TRANSIT,
        'warehouse' => ParcelStage::HUB,
        'courier_assigned' => ParcelStage::ASSIGN,
        'delivered' => ParcelStage::DONE,
        'partial' => ParcelStage::PARTIAL,
        'returned' => ParcelStage::RETURNED,
    ];

    /** Les 33 constantes du socle, lues à la source. */
    private function codes(): array
    {
        $source = file_get_contents(base_path('app/Enums/ParcelStatus.php'));
        preg_match_all('/const\s+([A-Z_]+)\s*=\s*(\d+)/', $source, $m);

        return array_combine($m[1], array_map('intval', $m[2]));
    }

    // — le web et l'app lisent le même colis au même stade ------------------

    /**
     * LE test de ce lot. `mobile/src/domain/parcelStatus.ts` fait déjà la
     * réduction 33 → 7 ; sa table est la référence. On la lit vraiment et on
     * compare code par code : une retouche d'un côté sans l'autre fait échouer
     * la suite, plutôt que de laisser un marchand voir deux stades différents
     * pour le même colis selon qu'il regarde son téléphone ou le back-office.
     */
    public function test_the_php_table_matches_the_merchant_app_code_by_code(): void
    {
        $fichier = base_path('../mobile/src/domain/parcelStatus.ts');
        $this->assertFileExists($fichier, 'la table de référence');

        // `[B.PENDING]: 'pending',` — la forme de STAGE_BY_CODE.
        preg_match_all(
            "/\[B\.([A-Z_]+)\]:\s*'([a-z_]+)'/",
            file_get_contents($fichier),
            $m,
            PREG_SET_ORDER
        );
        $this->assertGreaterThanOrEqual(
            33,
            count($m),
            'la table de l\'app a changé de forme : ce test ne la lit plus'
        );

        $codes = $this->codes();
        $web = ParcelStage::map();

        foreach ($m as [, $constante, $etapeApp]) {
            $this->assertArrayHasKey($constante, $codes, "ParcelStatus::{$constante}");
            $this->assertArrayHasKey(
                $etapeApp,
                self::CORRESPONDANCE,
                "l'app déclare l'étape « {$etapeApp} », que le web ne sait pas traduire"
            );

            $code = $codes[$constante];
            $this->assertSame(
                self::CORRESPONDANCE[$etapeApp],
                $web[$code] ?? null,
                "{$constante} ({$code}) : l'app dit « {$etapeApp} », le web dit autre chose"
            );
        }
    }

    /** Les 33 codes sont rangés. C'est le trou de 14 codes du socle qui se ferme. */
    public function test_every_one_of_the_thirty_three_codes_has_a_stage(): void
    {
        $codes = $this->codes();
        $this->assertCount(33, $codes, 'le socle a changé de nombre de statuts');

        $manquants = array_diff($codes, array_keys(ParcelStage::map()));
        $this->assertSame([], $manquants, 'codes sans étape : ' . implode(', ', array_keys($manquants)));
    }

    /**
     * Le socle n'avait pas de `else` : un code hors liste donnait « Undefined
     * variable $status » et une cellule vide. Le repli est le NEUTRE — mieux
     * vaut une pastille grise qu'un vert ou un rouge inventé — et le libellé
     * reste celui du backend, donc l'opérateur lit toujours le vrai statut.
     */
    public function test_an_unknown_code_falls_back_instead_of_breaking(): void
    {
        foreach ([0, 99, -1, null, '', 'abc'] as $inconnu) {
            $this->assertSame(ParcelStage::WAIT, ParcelStage::of($inconnu));

            $pastille = ParcelStage::pill($inconnu);
            $this->assertStringContainsString('bl-pill--' . ParcelStage::WAIT, $pastille);
            $this->assertNotSame('', trim(strip_tags($pastille)), 'jamais une cellule vide');
        }
    }

    // — la charte, statut par statut ----------------------------------------

    public function test_a_pending_parcel_is_not_an_incident(): void
    {
        // Le socle le peignait en `badge-danger`. Or le rouge est réservé à
        // l'incident : un colis qui attend son ramassage n'en est pas un.
        $this->assertSame(ParcelStage::WAIT, ParcelStage::of(ParcelStatus::PENDING));
        $this->assertFalse(ParcelStage::isIncident(ParcelStatus::PENDING));
    }

    public function test_only_a_delivered_parcel_is_green(): void
    {
        foreach ([ParcelStatus::DELIVERED, ParcelStatus::DELIVER] as $livre) {
            $this->assertSame(ParcelStage::DONE, ParcelStage::of($livre));
        }

        // Le socle mettait « reçu par le ramasseur » en vert, c'est-à-dire de la
        // couleur du colis arrivé — alors qu'il vient tout juste de partir.
        $this->assertNotSame(ParcelStage::DONE, ParcelStage::of(ParcelStatus::RECEIVED_BY_PICKUP_MAN));
        $this->assertNotSame(ParcelStage::DONE, ParcelStage::of(ParcelStatus::RETURN_RECEIVED_BY_MERCHANT));
    }

    public function test_a_partial_delivery_is_an_incident_not_a_success(): void
    {
        // Le défaut le plus coûteux : vert sur un relevé de règlement, alors que
        // l'argent encaissé ne correspond pas à la commande.
        $this->assertSame(ParcelStage::PARTIAL, ParcelStage::of(ParcelStatus::PARTIAL_DELIVERED));
        $this->assertTrue(ParcelStage::isIncident(ParcelStatus::PARTIAL_DELIVERED));
        $this->assertNotSame(ParcelStage::DONE, ParcelStage::of(ParcelStatus::PARTIAL_DELIVERED));
    }

    /** Les retours formaient trois familles (`dark`, `info`, `success`). Une seule. */
    public function test_every_return_status_reads_as_one_family(): void
    {
        $retours = [
            ParcelStatus::RETURN_WAREHOUSE,
            ParcelStatus::ASSIGN_MERCHANT,
            ParcelStatus::RETURNED_MERCHANT,
            ParcelStatus::RETURN_TO_COURIER,
            ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
            ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ];

        foreach ($retours as $code) {
            $this->assertSame(ParcelStage::RETURNED, ParcelStage::of($code), "statut {$code}");
            $this->assertTrue(ParcelStage::isIncident($code), "statut {$code}");
        }
    }

    /**
     * Une annulation ramène le colis à l'étape AMONT — elle ne crée pas d'étape
     * « annulé ». Deux cas ne sont pas symétriques et méritent d'être fixés :
     * annuler une livraison rend le colis au livreur, pas à l'entrepôt.
     */
    public function test_a_cancellation_returns_the_parcel_upstream(): void
    {
        $attendu = [
            ParcelStatus::PICKUP_ASSIGN_CANCEL => ParcelStage::WAIT,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL => ParcelStage::TRANSIT,
            ParcelStatus::RECEIVED_WAREHOUSE_CANCEL => ParcelStage::HUB,
            ParcelStatus::DELIVERED_CANCEL => ParcelStage::ASSIGN,
            ParcelStatus::PARTIAL_DELIVERED_CANCEL => ParcelStage::ASSIGN,
        ];

        foreach ($attendu as $code => $etape) {
            $this->assertSame($etape, ParcelStage::of($code), "statut {$code}");
        }
    }

    // — la triple copie a bien disparu --------------------------------------

    /**
     * Les trois points d'entrée doivent rendre EXACTEMENT la même pastille pour
     * le même code. C'est ce qui prouve que la table n'existe plus qu'une fois.
     */
    public function test_the_three_entry_points_agree(): void
    {
        foreach ($this->codes() as $nom => $code) {
            if ($code === ParcelStatus::TRANSFER_TO_HUB) {
                continue; // seul cas qui ajoute la route entre hubs (testé plus bas)
            }

            $attendue = ParcelStage::pill($code);

            $this->assertSame($attendue, StatusParcel($code), "StatusParcel({$nom})");

            $colis = new Parcel();
            $colis->forceFill(['status' => $code]);
            $this->assertSame($attendue, $colis->parcel_status, "accesseur ({$nom})");
        }
    }

    public function test_the_pill_carries_the_backend_label_escaped(): void
    {
        $pastille = ParcelStage::pill(ParcelStatus::DELIVERED);

        $this->assertStringContainsString('class="bl-pill bl-pill--done"', $pastille);
        $this->assertSame(
            trans('parcelStatus.' . ParcelStatus::DELIVERED),
            trim(html_entity_decode(strip_tags($pastille))),
            'le libellé reste celui du backend, jamais réécrit ici'
        );
        // La sortie part dans un {!! !!} : rien ne doit pouvoir s'y injecter
        // depuis un fichier de langue.
        $this->assertStringContainsString('e(trans(', file_get_contents(
            base_path('app/Services/Parcel/ParcelStage.php')
        ));
    }

    /**
     * Le socle ajoutait la route d'un transfert entre hubs — utile — mais en
     * ROUGE, avec un « To » anglais, et en lisant `$this->hub->name` sans garde :
     * un colis dont le hub a disparu faisait tomber la page.
     */
    public function test_the_hub_transfer_keeps_its_route_without_crashing(): void
    {
        $source = file_get_contents(base_path('app/Models/Backend/Parcel.php'));

        $this->assertMatchesRegularExpression(
            '/TRANSFER_TO_HUB\s*&&\s*\$this->hub\s*&&\s*\$this->transferhub/',
            $source,
            'la lecture des deux hubs doit être gardée'
        );
        $this->assertStringNotContainsString("' To '", $source, 'le « To » anglais');
        $this->assertStringContainsString('bl-pill--hub', $source, 'un transfert n\'est pas un incident');
        $this->assertSame(ParcelStage::HUB, ParcelStage::of(ParcelStatus::TRANSFER_TO_HUB));
    }

    // — plus aucune vue ne peint un statut à la main ------------------------

    /**
     * Quatre vues vivantes peignaient « livraison partielle » en vert et un
     * retour en bleu, à la main. Elles passent par `StatusParcel()`. Ce test
     * empêche la recopie de revenir — c'est par là que la divergence s'installe.
     */
    public function test_no_live_view_paints_a_status_badge_by_hand(): void
    {
        $vues = [
            'backend/parcel/index.blade.php',
            'backend/hub/view.blade.php',
            'backend/merchant/invoice/invoice_details.blade.php',
            'backend/merchant_panel/invoice/invoice_details.blade.php',
        ];

        foreach ($vues as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));

            $this->assertDoesNotMatchRegularExpression(
                '/badge-(success|info|danger|dark|primary|warning)[^>]*>\s*\{\{\s*trans\("?\'?parcelStatus/',
                $source,
                "{$vue} peint encore un statut à la main"
            );
            $this->assertStringContainsString('StatusParcel(', $source, $vue);
        }
    }

    /**
     * Les pastilles s'affichent aussi dans trois documents AUTONOMES, qui ne
     * chargent ni Bootstrap ni les feuilles de thème. Sans `tokens.css`, les
     * `var(--bl-pill-*)` de `components.css` seraient vides : des pastilles
     * transparentes, sans erreur visible.
     */
    public function test_the_standalone_print_documents_load_the_pill_styles(): void
    {
        $documents = [
            'backend/parcel/bulk_print.blade.php',
            'backend/reports/parcel_reports_print.blade.php',
            'backend/merchant_panel/reports/parcel_reports_print.blade.php',
        ];

        foreach ($documents as $doc) {
            $source = file_get_contents(resource_path('views/' . $doc));
            $this->assertStringContainsString('beninlink/css/tokens.css', $source, $doc);
            $this->assertStringContainsString('beninlink/css/components.css', $source, $doc);
        }

        // Et la mise en page du back-office, où vivent les six vues vivantes.
        $this->assertStringContainsString(
            'beninlink/css/components.css',
            file_get_contents(resource_path('views/backend/partials/header.blade.php'))
        );

        // Le site public, lui, ne la charge PAS : aucune de ses vues ne rend de
        // pastille. Sa chronologie de suivi a la sienne (timeline.css), à aligner
        // au lot 7. Une feuille inutilisée, c'est une requête pour rien — et ce
        // dépôt en compte déjà ~150 vers des CDN.
        $this->assertStringNotContainsString(
            'beninlink/css/components.css',
            file_get_contents(resource_path('views/frontend/layouts/master.blade.php'))
        );
    }

    /** Les sept familles sont définies, et le composant vit en un seul endroit. */
    public function test_the_seven_families_are_all_styled_once(): void
    {
        $composants = file_get_contents(public_path('beninlink/css/components.css'));
        $backoffice = file_get_contents(public_path('beninlink/css/theme-backoffice.css'));

        foreach (ParcelStage::families() as $famille) {
            $this->assertStringContainsString(".bl-pill--{$famille}", $composants, $famille);
        }
        $this->assertCount(7, ParcelStage::families());

        // Pas de seconde définition : c'est ce qui fait diverger deux surfaces.
        $this->assertStringNotContainsString('.bl-pill--', $backoffice, 'le composant est en double');
    }
}
