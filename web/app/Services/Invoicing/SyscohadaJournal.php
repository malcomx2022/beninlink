<?php

namespace App\Services\Invoicing;

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

/**
 * Écritures comptables d'un relevé de règlement, prêtes pour un import
 * dans un logiciel tenu selon le SYSCOHADA.
 *
 * Le plan de comptes et la lecture comptable sont dans `config/syscohada.php`
 * (à valider par l'expert-comptable). Ici, seulement la mécanique : une
 * ligne par imputation, débits et crédits équilibrés par écriture.
 */
class SyscohadaJournal
{
    public const COLUMNS = [
        'date', 'journal', 'piece', 'compte', 'libelle_compte',
        'libelle_ecriture', 'debit', 'credit', 'tiers_ifu', 'reference',
    ];

    /** @return array<int, array<string, string|int>> */
    public static function linesFor(Invoice $invoice): array
    {
        $statement = SettlementStatement::for($invoice);
        $t = $statement['totals'];
        $accounts = config('syscohada.accounts');
        $journals = config('syscohada.journals');

        $date = $statement['issued_on']->format('Y-m-d');
        $piece = $statement['number'];
        $merchant = $statement['merchant']['name'];
        $ifu = $statement['merchant']['ifu'];
        $auxiliaire = self::auxiliaire($invoice);

        $line = function (string $journal, string $account, string $label, int $debit, int $credit) use ($accounts, $date, $piece, $ifu, $auxiliaire): array {
            return [
                'date' => $date,
                'journal' => $journal,
                'piece' => $piece,
                'compte' => self::compte($accounts[$account]['code'], $account, $auxiliaire),
                'libelle_compte' => $accounts[$account]['label'],
                'libelle_ecriture' => $label,
                'debit' => $debit,
                'credit' => $credit,
                'tiers_ifu' => $ifu,
                'reference' => $piece,
            ];
        };

        $lines = [];

        // 1. Ventes : la prestation facturée au marchand.
        if ($t['fees_ttc'] > 0) {
            $label = __('statement.journal_sales', ['number' => $piece, 'merchant' => $merchant]);
            $lines[] = $line($journals['sales'], 'customers', $label, $t['fees_ttc'], 0);
            $lines[] = $line($journals['sales'], 'delivery_services', $label, 0, $t['fees_ht']);
            if ($t['vat'] > 0) {
                $lines[] = $line($journals['sales'], 'vat_collected', $label, 0, $t['vat']);
            }
        }

        // 2. Compensation : les frais sont retenus sur le COD encaissé.
        if ($t['fees_ttc'] > 0) {
            $label = __('statement.journal_settlement', ['number' => $piece, 'merchant' => $merchant]);
            $lines[] = $line($journals['settlement'], 'cod_liability', $label, $t['fees_ttc'], 0);
            $lines[] = $line($journals['settlement'], 'customers', $label, 0, $t['fees_ttc']);
        }

        // 3. Reversement du net, seulement une fois le relevé payé.
        if ((int) $invoice->status === InvoiceStatus::PAID && $t['net'] > 0) {
            $label = __('statement.journal_payout', ['number' => $piece, 'merchant' => $merchant]);
            $lines[] = $line($journals['bank'], 'cod_liability', $label, $t['net'], 0);
            $lines[] = $line($journals['bank'], 'bank', $label, 0, $t['net']);
        }

        return $lines;
    }

    /**
     * Suffixe auxiliaire du marchand, ou `null` si l'on tient un compte collectif.
     *
     * Question 1 de **D2** : seuls les comptes de tiers reçoivent l'auxiliaire —
     * un produit (7061) ou la TVA (4431) n'a pas de tiers, et le suffixer
     * rendrait la balance illisible.
     */
    private static function auxiliaire(Invoice $invoice): ?string
    {
        if (config('syscohada.auxiliary') !== 'merchant_code') {
            return null;
        }

        $code = (string) ($invoice->merchant?->merchant_unique_id ?? '');
        $code = preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '';

        return $code === '' ? null : strtoupper($code);
    }

    /** Comptes de tiers auxiliarisés ; les autres restent collectifs. */
    private static function compte(string $racine, string $account, ?string $auxiliaire): string
    {
        if ($auxiliaire === null || !in_array($account, ['customers', 'cod_liability'], true)) {
            return $racine;
        }

        return substr($racine . $auxiliaire, 0, (int) config('syscohada.auxiliary_length', 13));
    }

    /**
     * Contrôle d'équilibre, par pièce et en total.
     *
     * Un import comptable refuse un lot déséquilibré, souvent sans dire lequel.
     * Le vérifier ici, avant l'export, coûte trois lignes.
     *
     * @param array<int, array<string, string|int>> $lines
     * @return array{debit:int, credit:int, pieces:int, desequilibrees:array<int,string>}
     */
    public static function balance(array $lines): array
    {
        $parPiece = [];
        foreach ($lines as $ligne) {
            $cle = $ligne['journal'] . '/' . $ligne['piece'];
            $parPiece[$cle] ??= ['debit' => 0, 'credit' => 0];
            $parPiece[$cle]['debit'] += (int) $ligne['debit'];
            $parPiece[$cle]['credit'] += (int) $ligne['credit'];
        }

        $desequilibrees = [];
        foreach ($parPiece as $cle => $totaux) {
            if ($totaux['debit'] !== $totaux['credit']) {
                $desequilibrees[] = $cle;
            }
        }

        return [
            'debit' => array_sum(array_column($lines, 'debit')),
            'credit' => array_sum(array_column($lines, 'credit')),
            'pieces' => count($parPiece),
            'desequilibrees' => $desequilibrees,
        ];
    }

    /** Contenu CSV (séparateur et BOM selon la configuration). */
    public static function csv(iterable $invoices): string
    {
        $delimiter = (string) config('syscohada.csv.delimiter', ';');
        $handle = fopen('php://temp', 'r+');
        if (config('syscohada.csv.bom', true)) {
            fwrite($handle, "\xEF\xBB\xBF");
        }
        fputcsv($handle, self::COLUMNS, $delimiter);
        foreach ($invoices as $invoice) {
            foreach (self::linesFor($invoice) as $row) {
                fputcsv($handle, array_values($row), $delimiter);
            }
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}
