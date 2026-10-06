<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * **S91 (M4)** — l'app marchand ne sert plus que le barème par zones.
 *
 * Pendant la transition de **D4**, l'écran Tarifs retombait sur les quatre
 * colonnes héritées quand le serveur ne servait pas de zones, et le formulaire
 * de création offrait « Barème hérité (par type de livraison) » (valeur 0).
 * Depuis l'étape 6 (2026-09-07) la route est le seul axe de tarification :
 * `zone_id` est **obligatoire** à la création, et cette entrée ne menait plus
 * qu'à un refus du serveur. Depuis S86 aucune société ne se déploie sans zones.
 *
 * Ce filet lit les sources de l'app, comme `MerchantAppCustomsContractTest` :
 * le contrat vit dans `web/`, l'app le suit, et un retour de la seconde forme
 * serait une régression visible du marchand (un choix qui échoue toujours).
 */
class MerchantAppPricingContractTest extends TestCase
{
    private function source(string $relatif): string
    {
        $chemin = dirname(base_path()) . '/mobile/' . $relatif;
        $this->assertFileExists($chemin);

        return file_get_contents($chemin);
    }

    public function test_the_creation_form_offers_one_choice_per_zone_and_no_legacy_route(): void
    {
        $formulaire = $this->source('app/(app)/parcel/new.tsx');
        $this->assertStringNotContainsString('legacyRoute', $formulaire, 'le « barème hérité » n\'est plus proposé : zone_id est obligatoire');
        $this->assertStringNotContainsString("value: 0, label", $formulaire, 'aucune entrée de valeur 0 dans les zones');
        $this->assertStringContainsString('zoneChoices(', $formulaire, 'les choix viennent du module de domaine');

        $domaine = $this->source('src/domain/zoneChoices.ts');
        $this->assertMatchesRegularExpression('/return zones\.map\(\(zone\) => \(\{ value: zone\.id, label: zone\.name \}\)\);/', $domaine, 'une entrée par zone, et rien devant');
        $this->assertStringNotContainsString('value: 0', $domaine, 'pas de « barème hérité » dans le domaine non plus');
        $this->assertFileExists(dirname(base_path()) . '/mobile/src/domain/zoneChoices.test.ts');
    }

    public function test_the_rates_screen_has_a_single_form(): void
    {
        $ecran = $this->source('app/(app)/rates.tsx');
        // `zone.rates` est le barème de la zone, légitime : ce qu'on refuse, c'est l'état `rates` à l'écran.
        $this->assertStringNotContainsString('setRates(', $ecran, 'plus d\'état `rates` : plus de repli sur les colonnes héritées');
        $this->assertStringNotContainsString('grid.rates', $ecran);
        $this->assertStringNotContainsString('DeliveryRate', $ecran, 'le type des colonnes héritées n\'est plus lu par l\'écran');
        $this->assertStringContainsString("t('rates.noZone')", $ecran, 'un transporteur sans zones est dit comme une anomalie');
    }

    public function test_the_translations_follow(): void
    {
        $fr = $this->source('src/i18n/fr.ts');
        $this->assertStringNotContainsString('legacyRoute', $fr);
        $this->assertStringContainsString('noZone:', $fr);
    }
}
