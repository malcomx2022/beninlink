<?php

use App\Services\Brand\AccentColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3 de la charte web (2026-09-18) — l'ocre devient réglable par transporteur.
 *
 * Le socle offre déjà `primary_color` et `text_color` à chaque transporteur ;
 * l'ocre, lui, était figé dans `tokens.css` depuis le lot 1. L'arbitrage §9.3 de
 * l'audit a tranché en faveur de l'option (b) : « la charte est un défaut
 * d'usine, pas une prison ».
 *
 * Contrairement à la migration du lot 1, celle-ci n'a AUCUNE donnée à convertir :
 * la colonne naît avec le bon défaut, et toutes les lignes existantes le
 * reçoivent. Il n'y a donc pas de valeur d'usine We Courier à épargner ici.
 *
 * Le défaut de colonne est porteur, pas cosmétique : `CompanyRepository::
 * company_create()` ne renseigne aucune couleur, donc chaque nouveau
 * transporteur hérite de ce défaut — c'est le constat du lot 1 (§10.3 b).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('general_settings') || Schema::hasColumn('general_settings', 'accent_color')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            // `after()` est ignoré par SQLite et honoré par MySQL : la colonne
            // se range à côté de sa jumelle là où quelqu'un lira la table.
            $table->string('accent_color')
                ->default(AccentColor::CHARTE)
                ->nullable()
                ->after('primary_color');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('general_settings') || ! Schema::hasColumn('general_settings', 'accent_color')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('accent_color');
        });
    }
};
