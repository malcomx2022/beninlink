<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **D2, décisions du 2026-10-03 (S73).**
 *
 * - `parcels.return_vat_amount` : la TVA sur le frais de retour (question 6 —
 *   le retour est une prestation **taxable** au taux normal). Écrite au moment
 *   du retour, comme `return_charges`, et lue telle quelle par le relevé : on
 *   facture ce qui a été prélevé, jamais un recalcul. Les colis antérieurs
 *   gardent `0` — leur relevé a facturé le retour hors champ, et c'est
 *   `beninlink:retours-sans-tva` qui les montre, sans les toucher.
 * - `invoices.paid_on` : la date du passage au statut **payé** (question 5 —
 *   la date de l'ordre de virement fait foi). C'est la date de l'écriture de
 *   banque ; les relevés payés avant cette migration n'en ont pas et l'écriture
 *   retombe sur `issued_on`, comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->decimal('return_vat_amount', 13, 2)->default(0)->after('return_charges')
                ->comment('TVA sur le frais de retour, prélevée au retour (D2 q.6)');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->date('paid_on')->nullable()->after('issued_on')
                ->comment('date du passage à payé = date de l’ordre de virement (D2 q.5)');
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn('return_vat_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paid_on');
        });
    }
};
