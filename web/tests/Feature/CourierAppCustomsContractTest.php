<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * **S95** — l'app livreur lit `customs_alerts` sur sa course et la montre en lecture seule.
 *
 * Comme `MerchantAppCustomsContractTest` (S82) et `DeliverymanAppContractTest` (S85) :
 * le contrat vit dans `web/`, l'app le suit, et la propriété se lit dans ses sources
 * (la carte, elle, est **rendue** par `CustomsNotice.test.tsx`, jest-expo).
 */
class CourierAppCustomsContractTest extends TestCase
{
    private function source(string $relatif): string
    {
        $chemin = dirname(base_path()) . '/mobile-livreur/' . $relatif;
        $this->assertFileExists($chemin);

        return file_get_contents($chemin);
    }

    public function test_the_details_module_reads_customs_alerts_with_a_fallback_for_older_servers(): void
    {
        $module = $this->source('src/api/deliveryman.ts');
        $this->assertStringContainsString('customs_alerts ?? []', $module, 'la lecture, avec repli pour un serveur d\'avant S95');
        $this->assertStringContainsString('customsAlerts', $module);
    }

    public function test_the_course_screen_shows_the_notice_and_the_notice_offers_no_action(): void
    {
        $ecran = $this->source('app/(app)/parcel/[id]/index.tsx');
        $this->assertStringContainsString('<CustomsNotice alerts={customsAlerts} />', $ecran, 'la carte est sur la course');
        $this->assertStringContainsString('customsAlerts: a', $ecran, 'l\'écran lit ce que le module rend');

        $carte = $this->source('src/components/CustomsNotice.tsx');
        $this->assertStringNotContainsString('Button', $carte, 'le livreur lit ; le traitement est au transporteur (S68)');
        $this->assertStringNotContainsString('resolve', $carte);
        $this->assertStringContainsString('customsLevelColorName(alert.level)', $carte, 'la gravité porte la couleur de la charte');
        $this->assertStringContainsString('if (alerts.length === 0) return null;', $carte, 'rien sur un colis domestique');
        $this->assertFileExists(dirname(base_path()) . '/mobile-livreur/src/components/CustomsNotice.test.tsx', 'la carte est rendue en test');
    }

    /** Les niveaux sont ceux du contrat, recopiés à l'identique des énumérations de `web/`. */
    public function test_the_courier_levels_are_the_servers(): void
    {
        $domaine = $this->source('src/domain/customsLevel.ts');
        $this->assertStringContainsString('INFO: ' . \App\Enums\CustomsLevel::INFO . ',', $domaine);
        $this->assertStringContainsString('WARNING: ' . \App\Enums\CustomsLevel::WARNING . ',', $domaine);
        $this->assertStringContainsString('BLOCKING: ' . \App\Enums\CustomsLevel::BLOCKING . ',', $domaine);
        $this->assertStringContainsString('PENDING: ' . \App\Enums\CustomsAlertStatus::PENDING . ',', $domaine);
        $this->assertStringContainsString('RESOLVED: ' . \App\Enums\CustomsAlertStatus::RESOLVED . ',', $domaine);

        $fr = $this->source('src/i18n/fr.ts');
        $this->assertStringContainsString('customs: {', $fr);
        $this->assertStringContainsString('requiredDocument:', $fr);
    }

    /**
     * S101 — la liste des courses signale le document à collecter sans ouvrir la course (`customs_pending`).
     * S103 — la carte est un composant extrait (`ParcelCard`) et RENDUE en test : la pastille, le montant
     * en FCFA entiers et les boutons Appeler / Itinéraire ne sont plus une lecture de source.
     */
    public function test_the_course_list_shows_the_customs_badge_from_customs_pending(): void
    {
        $liste = $this->source('app/(app)/(tabs)/index.tsx');
        $this->assertStringContainsString('<ParcelCard', $liste, 'la liste rend ses courses par le composant extrait (S103)');
        $this->assertStringNotContainsString('customs_pending', $liste, 'la pastille vit dans la carte, pas dans l\'écran');

        $carte = $this->source('src/components/ParcelCard.tsx');
        $this->assertStringContainsString('(parcel.customs_pending ?? 0) > 0', $carte, 'la pastille suit `customs_pending`, avec repli pour un serveur d\'avant S101');
        $this->assertStringContainsString("t('customs.badge')", $carte);
        $this->assertStringContainsString('formatAmount(parcel.cash_collection)', $carte, 'le montant à encaisser passe par le formateur FCFA entiers');
        $this->assertFileExists(dirname(base_path()) . '/mobile-livreur/src/components/ParcelCard.test.tsx',
            'le test de rendu de la carte a disparu : la propriété redevient une lecture de source');

        $this->assertStringContainsString('customs_pending?: number;', $this->source('src/api/types.ts'));
        $this->assertStringContainsString('badge:', $this->source('src/i18n/fr.ts'));
    }
}
