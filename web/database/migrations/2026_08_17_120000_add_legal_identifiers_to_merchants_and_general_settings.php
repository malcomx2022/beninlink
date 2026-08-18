<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identifiants légaux béninois — chantier 2.
 *
 * Deux porteurs, parce qu'une facture SYSCOHADA doit mentionner les identifiants
 * des DEUX parties (DAT §2.6) :
 *   - `merchants`         : la PME cliente (recueillis à l'inscription) ;
 *   - `general_settings`  : le transporteur, c'est-à-dire le locataire lui-même.
 *
 * Colonnes **nullable** : les marchands déjà enregistrés n'ont pas ces données,
 * et une colonne `NOT NULL` sans valeur par défaut ferait échouer la migration
 * sur une base peuplée. L'obligation est portée par la validation des
 * formulaires, pas par le schéma — c'est ce qui permet de régulariser un
 * marchand existant sans bloquer l'application.
 *
 * ⚠️ Première migration **additive** du dépôt : tout le socle We Courier tient
 * dans ses migrations de création. Ne pas prendre les `create_*` pour modèle.
 *
 * À ne pas confondre avec l'existant :
 *   - `merchants.trade_license` et `merchants.nid_id` sont des `foreignId` vers
 *     `uploads` — des **scans**, pas des numéros. Ils restent en place comme
 *     justificatifs, à côté des numéros ajoutés ici.
 *   - `users.nid_number` est la pièce d'identité de la **personne** physique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // IFU : Identifiant Fiscal Unique, 13 chiffres au Bénin.
            $table->string('ifu', 13)->nullable()->after('business_name');
            // RCCM : Registre du Commerce et du Crédit Mobilier, ex. RB/COT/24 B 1234.
            $table->string('rccm', 50)->nullable()->after('ifu');
            // CNSS : numéro employeur. Ne concerne que les entreprises ayant des salariés.
            $table->string('cnss', 30)->nullable()->after('rccm');
        });

        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('ifu', 13)->nullable()->after('name');
            $table->string('rccm', 50)->nullable()->after('ifu');
            $table->string('cnss', 30)->nullable()->after('rccm');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['ifu', 'rccm', 'cnss']);
        });

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['ifu', 'rccm', 'cnss']);
        });
    }
};
