<?php

use App\Enums\VatStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * **R7 b (S75)** — le statut TVA du marchand devient explicite.
 *
 * `0` dans `merchants.vat` voulait dire « pas saisi », jamais « exonéré » : un
 * marchand exonéré n'avait aucun moyen de le dire, et un `0` oublié et un `0`
 * voulu se lisaient pareil sur la facture — une ambiguïté fiscale. Le statut
 * tranche : `unset` / `taxable` / `exempt` (`App\Enums\VatStatus`).
 *
 * Reprise des lignes existantes **sans reclassification** : un taux propre
 * saisi (`vat > 0`) devient `taxable` — c'est ce que le calcul appliquait déjà
 * — et tout le reste reste `unset`, où le taux de la société s'applique comme
 * avant (D1). Personne ne devient exonéré par migration : ce statut se déclare
 * à l'écran, marchand par marchand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('vat_status', 16)->default(VatStatus::UNSET)->after('vat')
                ->comment('unset = taux société (D1) ; taxable = taux propre `vat` ; exempt = exonéré (R7b)');
        });

        DB::table('merchants')->where('vat', '>', 0)->update(['vat_status' => VatStatus::TAXABLE]);
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('vat_status');
        });
    }
};
