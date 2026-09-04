<?php

namespace App\Services\Invoicing;

use App\Models\Backend\GeneralSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Numérotation des relevés de règlement : `PREFIXE-AAAA-NNNNNN`.
 *
 * Une séquence par société **et par exercice** (année civile), incrémentée
 * sous verrou de ligne : deux générations simultanées (cron et bouton manuel)
 * ne peuvent ni sauter ni partager un numéro. La ligne de séquence est créée
 * au premier relevé de l'exercice. Le préfixe est celui de la société
 * (`general_settings.invoice_prefix`), lu explicitement par `company_id` —
 * jamais via `settings()`, qui retombe sur la société 1 hors requête.
 *
 * ⚠️ À appeler **dans la transaction qui crée la facture** : si l'insertion
 * échoue, le numéro réservé est rendu avec le rollback, sans trou.
 */
class InvoiceNumbering
{
    public const DEFAULT_PREFIX = 'FAC';

    /** @return array{number:string, fiscal_year:int, sequence:int} */
    public function next(int $companyId, CarbonInterface $issuedOn): array
    {
        $year = (int) $issuedOn->format('Y');

        return DB::transaction(function () use ($companyId, $year) {
            $row = DB::table('invoice_sequences')
                ->where('company_id', $companyId)
                ->where('fiscal_year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('invoice_sequences')->insert([
                    'company_id' => $companyId,
                    'fiscal_year' => $year,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = DB::table('invoice_sequences')
                    ->where('company_id', $companyId)
                    ->where('fiscal_year', $year)
                    ->lockForUpdate()
                    ->first();
            }

            $sequence = (int) $row->last_number + 1;

            DB::table('invoice_sequences')
                ->where('id', $row->id)
                ->update(['last_number' => $sequence, 'updated_at' => now()]);

            return [
                'number' => $this->format($this->prefixFor($companyId), $year, $sequence),
                'fiscal_year' => $year,
                'sequence' => $sequence,
            ];
        });
    }

    public function format(string $prefix, int $year, int $sequence): string
    {
        return sprintf('%s-%d-%06d', $prefix, $year, $sequence);
    }

    public function prefixFor(int $companyId): string
    {
        $prefix = GeneralSettings::whereKey($companyId)->value('invoice_prefix');
        $prefix = Str::upper(trim((string) $prefix));

        return $prefix !== '' ? $prefix : self::DEFAULT_PREFIX;
    }
}
