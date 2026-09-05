<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rattachement des lignes de relevé marchand écrites sans marchand.
 *
 * Les **treize** écritures de `MerchantStatement` du cycle de vie d'un colis —
 * livraison, livraison partielle, leurs annulations, le retour reçu — ne
 * renseignaient pas `merchant_id`. Or c'est exactement la colonne que lit
 * l'écran « Mes relevés », côté panneau marchand comme côté API :
 * `MerchantStatement::where('merchant_id', …)`.
 *
 * Conséquence : le relevé du marchand ne montrait **aucune** ligne de
 * livraison — ni l'encaissement, ni les frais, ni la TVA. Son solde bougeait
 * sans rien pour l'expliquer, et c'est précisément ce que le relevé est censé
 * faire.
 *
 * Le rattachement est **sans ambiguïté** : chacune de ces lignes porte son
 * `parcel_id`, et un colis appartient à un marchand et un seul. On ne devine
 * rien — contrairement aux lignes de `settings` orphelines, qu'on a
 * délibérément laissées telles quelles faute de pouvoir les attribuer.
 *
 * Le correctif du code accompagne cette migration ; celle-ci rattrape ce qui
 * est déjà en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('merchant_statements')
            ->whereNull('merchant_id')
            ->whereNotNull('parcel_id')
            ->orderBy('id')
            ->chunkById(500, function ($lignes) {
                foreach ($lignes as $ligne) {
                    $merchantId = DB::table('parcels')->where('id', $ligne->parcel_id)->value('merchant_id');
                    if ($merchantId !== null) {
                        DB::table('merchant_statements')->where('id', $ligne->id)->update(['merchant_id' => $merchantId]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Volontairement vide : un rattachement certain ne se défait pas.
    }
};
