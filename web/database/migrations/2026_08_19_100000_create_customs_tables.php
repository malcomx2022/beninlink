<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertes douanieres UEMOA / CEDEAO — chantier 5 (Module 4 du TDR-L7).
 *
 * Deux tables et deux colonnes, pour une regle simple : un colis dont le pays de
 * destination n'est pas le Benin est un colis d'EXPORT, et un export peut exiger
 * un document. Trois niveaux, du DAT §2.5 : INFO (document recommande),
 * AVERTISSEMENT (document obligatoire), BLOQUANT (livraison interdite).
 *
 * `customs_rules` porte le referentiel — pays × categorie de marchandise. Il est
 * scope par societe comme le reste du socle : une societe peut affiner ses
 * regles sans toucher a celles d'une autre.
 *
 * `customs_alerts` garde la trace de ce qui a ete declenche, pour l'ecran
 * « En cours / Traitees » de la maquette. Une alerte n'est PAS un simple
 * affichage : elle est ecrite au moment de la creation du colis, avec le texte
 * de la regle telle qu'elle etait alors — si la regle change ensuite, l'alerte
 * deja emise garde ce qui a ete annonce au marchand.
 *
 * Le niveau BLOQUANT, lui, n'ecrit aucune alerte : la creation est refusee, il
 * n'y a pas de colis auquel la rattacher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customs_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()
                ->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');

            /** Code ISO 3166-1 alpha-2 du pays de DESTINATION (NG, TG, GH...). */
            $table->string('country_code', 2);
            /** Nom affiche, pour ne pas dependre d'une table de pays inexistante. */
            $table->string('country_name', 64);

            /** Categorie douaniere, en slug : alimentaire, textile, electronique... */
            $table->string('goods_category', 32);

            /** App\Enums\CustomsLevel : 1 info, 2 avertissement, 3 bloquant. */
            $table->unsignedTinyInteger('level');

            /** Document exige ou recommande, tel qu'il sera affiche au marchand. */
            $table->string('required_document', 191)->nullable();
            $table->text('message');

            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            /** Une seule regle par societe, pays et categorie. */
            $table->unique(['company_id', 'country_code', 'goods_category'], 'customs_rules_unique');
        });

        Schema::create('customs_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()
                ->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()
                ->constrained('merchants')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('parcel_id')->nullable()
                ->constrained('parcels')->onUpdate('cascade')->onDelete('cascade');

            /**
             * Regle d'origine, en `nullOnDelete` : supprimer une regle ne doit pas
             * effacer l'historique des alertes qu'elle a produites.
             */
            $table->foreignId('customs_rule_id')->nullable()
                ->constrained('customs_rules')->onUpdate('cascade')->nullOnDelete();

            /** Copie figee de la regle au moment du declenchement. */
            $table->string('country_code', 2);
            $table->string('country_name', 64);
            $table->string('goods_category', 32);
            $table->unsignedTinyInteger('level');
            $table->string('required_document', 191)->nullable();
            $table->text('message');

            /** App\Enums\CustomsAlertStatus : 1 en cours, 2 traitee. */
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'merchant_id', 'status'], 'customs_alerts_listing');
        });

        Schema::table('parcels', function (Blueprint $table) {
            /**
             * Vide ou « BJ » = colis domestique : rien ne change, aucune regle ne
             * s'applique. C'est ce qui rend la colonne retrocompatible avec les
             * colis existants.
             */
            $table->string('destination_country', 2)->nullable()->after('customer_address');
            $table->string('customs_category', 32)->nullable()->after('destination_country');
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn(['destination_country', 'customs_category']);
        });

        Schema::dropIfExists('customs_alerts');
        Schema::dropIfExists('customs_rules');
    }
};
