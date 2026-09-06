<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Taux COD de la zone CEDEAO (**D4**) — 3 %, tranché le 2026-09-06.
 *
 * `merchants.cod_charges` portait trois clés, une par zone nationale. La zone
 * d'export n'en avait pas : `codRateForZone()` rendait **0** pour elle, par
 * refus d'emprunter le taux « hors ville » — on ne devine pas un prix.
 *
 * Le montant est fixé ; la clé peut donc exister. Cette migration l'ajoute aux
 * marchands qui ne l'ont pas, **sans toucher** à ceux qui l'ont déjà : un taux
 * négocié ne se réécrit pas parce qu'on a passé une migration.
 *
 * ⚠️ La valeur retenue est le taux **par défaut** de la décision. Un marchand
 * dont le contrat prévoit autre chose se règle dans l'écran d'édition, comme
 * pour les trois autres zones.
 */
return new class extends Migration
{
    /** Taux par défaut de la zone d'export, en pourcentage. */
    private const TAUX = '3';

    public function up(): void
    {
        DB::table('merchants')->orderBy('id')->chunkById(200, function ($merchants) {
            foreach ($merchants as $merchant) {
                $taux = json_decode((string) $merchant->cod_charges, true);

                // Une colonne vide ou illisible n'est pas réparée ici : ce
                // serait inventer les trois autres taux au passage.
                if (!is_array($taux) || array_key_exists('cedeao', $taux)) {
                    continue;
                }

                $taux['cedeao'] = self::TAUX;

                DB::table('merchants')->where('id', $merchant->id)
                    ->update(['cod_charges' => json_encode($taux)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('merchants')->orderBy('id')->chunkById(200, function ($merchants) {
            foreach ($merchants as $merchant) {
                $taux = json_decode((string) $merchant->cod_charges, true);

                if (!is_array($taux) || !array_key_exists('cedeao', $taux)) {
                    continue;
                }

                unset($taux['cedeao']);

                DB::table('merchants')->where('id', $merchant->id)
                    ->update(['cod_charges' => json_encode($taux)]);
            }
        });
    }
};
