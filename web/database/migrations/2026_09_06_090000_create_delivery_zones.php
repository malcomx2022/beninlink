<?php

use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte du barème (**D4**) — étape 1 : les axes deviennent des lignes.
 *
 * Le socle mélangeait un **délai** (jour même, lendemain) et un **périmètre**
 * (périphérie, intérieur) dans quatre colonnes de `delivery_charges` : on ne
 * pouvait pas demander « lendemain à l'intérieur du pays », la case n'existait
 * pas. Le métier a tranché le 2026-09-06 :
 *
 *   - **zones** : Cotonou, Périphérie, Intérieur, CEDEAO ;
 *   - **délai global** : les mêmes délais partout, avec un **supplément par
 *     délai** indépendant de la zone ;
 *   - **CEDEAO** : un **forfait par pays**, pas un tarif au poids.
 *
 * ⚠️ Migration **additive** : les quatre colonnes restent, et rien ne les lit
 * différemment tant qu'une société n'a pas de zones. Une installation qui ne
 * fait rien continue de facturer exactement comme avant — c'est ce que vérifie
 * `DeliveryPricingBaselineTest`, écrit avant la refonte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('general_settings')->onDelete('cascade');
            $table->string('code', 32);          // cotonou, peripherie, interieur, cedeao
            $table->string('name');
            $table->integer('position')->default(0);
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // Délais : la liste est la même partout (« délai global »), seul le
        // supplément varie d'un délai à l'autre.
        Schema::create('delivery_delays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('general_settings')->onDelete('cascade');
            $table->string('code', 32);          // same_day, next_day, standard
            $table->string('name');
            $table->decimal('surcharge', 16, 2)->default(0);
            $table->integer('position')->default(0);
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // CEDEAO : un forfait par pays, indépendant du poids.
        Schema::create('delivery_zone_countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('delivery_zones')->onDelete('cascade');
            $table->string('code', 2);           // TG, NG, BF…
            $table->string('name');
            $table->decimal('flat_amount', 16, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE);
            $table->timestamps();

            $table->unique(['zone_id', 'code']);
        });

        // Une ligne de barème appartient désormais à une zone. `null` = ligne
        // héritée, lue par les quatre colonnes comme avant.
        foreach (['delivery_charges', 'merchant_delivery_charges'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('zone_id')->nullable()->after('category_id')
                    ->constrained('delivery_zones')->nullOnDelete();
                $t->decimal('amount', 16, 2)->nullable()->after('zone_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['delivery_charges', 'merchant_delivery_charges'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign($table . '_zone_id_foreign');
                $t->dropColumn(['zone_id', 'amount']);
            });
        }

        Schema::dropIfExists('delivery_zone_countries');
        Schema::dropIfExists('delivery_delays');
        Schema::dropIfExists('delivery_zones');
    }
};
