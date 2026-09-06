<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte du barème (**D4**) — étape 5 bis : le colis porte sa route.
 *
 * Les étapes 1 à 5 ont donné le schéma des zones, la résolution, la conversion,
 * les écrans et le contrat d'API. Il manquait le maillon qui rend la grille
 * **facturante** : `DeliveryChargeResolver::resolveByZone()` n'avait aucun
 * appelant en production, parce qu'un colis ne savait pas dire dans quelle zone
 * il va ni sous quel délai. Il ne portait qu'un `delivery_type_id`, c'est-à-dire
 * l'axe mélangé que D4 défait.
 *
 * ⚠️ Additive, comme les précédentes : `zone_id` et `delay_id` sont **nullables**
 * et `delivery_type_id` reste. Un colis sans zone est facturé exactement comme
 * avant — c'est ce que vérifie `DeliveryPricingBaselineTest`, écrit avant la
 * refonte et jamais modifié depuis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('delivery_type_id')
                ->constrained('delivery_zones')->nullOnDelete();
            $table->foreignId('delay_id')->nullable()->after('zone_id')
                ->constrained('delivery_delays')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropForeign('parcels_zone_id_foreign');
            $table->dropForeign('parcels_delay_id_foreign');
            $table->dropColumn(['zone_id', 'delay_id']);
        });
    }
};
