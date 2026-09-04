<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chantier 4 — numérotation conforme des relevés de règlement.
 *
 * Le socle numérotait `PREFIXE-<id_marchand><count+1>` (bloc G) : ambigu
 * (marchand 1 / n° 23 et marchand 12 / n° 3 donnent le même numéro), non
 * séquentiel (une suppression réutilise un numéro) et sans exercice. Un
 * numéro de facture doit être **unique, continu et chronologique par
 * exercice** ; on l'obtient par une séquence verrouillée par société et par
 * année (`invoice_sequences`) — voir App\Services\Invoicing\InvoiceNumbering.
 *
 * `invoices.invoice_date` reste la chaîne `d-m-Y` que les vues du socle
 * affichent ; `issued_on` (date) porte la même information sous une forme
 * triable et comparable en SQL. Les factures existantes sont reprises :
 * `issued_on` depuis `invoice_date`, puis une séquence par société et par
 * exercice dans l'ordre de création — sans changer leur `invoice_id`, déjà
 * remis aux marchands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'fiscal_year']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->date('issued_on')->nullable()->after('invoice_date');
            $table->unsignedSmallInteger('fiscal_year')->nullable()->after('issued_on');
            $table->unsignedInteger('sequence')->nullable()->after('fiscal_year');
            $table->unique(['company_id', 'fiscal_year', 'sequence'], 'invoices_company_year_sequence_unique');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_company_year_sequence_unique');
            $table->dropColumn(['issued_on', 'fiscal_year', 'sequence']);
        });
        Schema::dropIfExists('invoice_sequences');
    }

    private function backfill(): void
    {
        $counters = [];

        DB::table('invoices')->orderBy('id')->get()->each(function ($invoice) use (&$counters) {
            $issuedOn = $this->parseDate($invoice->invoice_date) ?? Carbon::parse($invoice->created_at);
            $year = (int) $issuedOn->format('Y');
            $key = ($invoice->company_id ?? 0) . ':' . $year;
            $counters[$key] = ($counters[$key] ?? 0) + 1;

            DB::table('invoices')->where('id', $invoice->id)->update([
                'issued_on' => $issuedOn->toDateString(),
                'fiscal_year' => $year,
                'sequence' => $counters[$key],
            ]);
        });

        foreach ($counters as $key => $last) {
            [$companyId, $year] = explode(':', $key);
            if ((int) $companyId === 0) {
                continue;
            }
            DB::table('invoice_sequences')->insert([
                'company_id' => (int) $companyId,
                'fiscal_year' => (int) $year,
                'last_number' => $last,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
};
