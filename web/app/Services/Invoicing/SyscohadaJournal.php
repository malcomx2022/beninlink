<?php

namespace App\Services\Invoicing;

use App\Enums\InvoiceStatus;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Wallet;
use App\Models\CashReceivedFromDeliveryman;
use Carbon\Carbon;

/**
 * Écritures comptables prêtes pour un import dans un logiciel tenu selon le
 * SYSCOHADA — décision **D2**.
 *
 * Le plan de comptes et la lecture comptable sont dans `config/syscohada.php`.
 * Ici, seulement la mécanique : une ligne par imputation, débits et crédits
 * équilibrés par écriture.
 *
 * Deux entrées :
 *
 * - `linesFor(Invoice)` : les écritures **d'un relevé** (ventes, compensation,
 *   et le reversement s'il est payé) — l'export relevé par relevé du back-office ;
 * - `periode(société, du, au)` : **l'extrait d'une période** pour l'expert-
 *   comptable (S73). Il range chaque écriture dans la période de **sa** date :
 *   ventes et compensation à l'émission du relevé, reversement à la date de
 *   l'ordre de virement (`paid_on`, question 5), recharges de portefeuille à
 *   leur approbation et remises d'espèces à leur date (question 8). Un relevé
 *   émis en août et payé en septembre a donc ses ventes dans l'extrait d'août
 *   et sa banque dans celui de septembre — c'est ce qu'un journal de banque
 *   attend.
 *
 * La société est toujours lue **explicitement** (identifiant) : ce service
 * tourne aussi hors requête, où `settings()` retomberait sur la société 1 (F4).
 */
class SyscohadaJournal
{
    public const COLUMNS = [
        'date', 'journal', 'piece', 'compte', 'libelle_compte',
        'libelle_ecriture', 'debit', 'credit', 'tiers_ifu', 'reference',
    ];

    /* ───────────────────────── un relevé ─────────────────────────────────── */

    /** @return array<int, array<string, string|int>> */
    public static function linesFor(Invoice $invoice): array
    {
        return array_merge(self::ventesEtCompensation($invoice), self::reversement($invoice));
    }

    /**
     * Ventes (VE) et compensation (OD), datées de l'émission du relevé.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function ventesEtCompensation(Invoice $invoice): array
    {
        $statement = SettlementStatement::for($invoice);
        $t = $statement['totals'];
        $journals = config('syscohada.journals');
        $date = $statement['issued_on']->format('Y-m-d');
        $piece = $statement['number'];
        $merchant = $statement['merchant']['name'];
        $line = self::ligne($date, $piece, $statement['merchant']['ifu'], self::auxiliaire($invoice->merchant));

        $lines = [];

        // 1. Ventes : la prestation facturée au marchand — livraison ET retour
        //    (question 6 : le frais de retour est taxable, il est dans le HT).
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

        return $lines;
    }

    /**
     * Reversement du net (BQ), seulement une fois le relevé payé, **à la date
     * de l'ordre de virement** (`paid_on`, question 5). Un relevé payé avant
     * que cette date existe retombe sur son émission, comme avant.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function reversement(Invoice $invoice): array
    {
        if ((int) $invoice->status !== InvoiceStatus::PAID) {
            return [];
        }

        $statement = SettlementStatement::for($invoice);
        $t = $statement['totals'];
        if ($t['net'] <= 0) {
            return [];
        }

        $journals = config('syscohada.journals');
        $piece = $statement['number'];
        $date = self::datePaiement($invoice)->format('Y-m-d');
        $line = self::ligne($date, $piece, $statement['merchant']['ifu'], self::auxiliaire($invoice->merchant));
        $label = __('statement.journal_payout', ['number' => $piece, 'merchant' => $statement['merchant']['name']]);

        return [
            $line($journals['bank'], 'cod_liability', $label, $t['net'], 0),
            $line($journals['bank'], 'bank', $label, 0, $t['net']),
        ];
    }

    /** La date comptable du reversement : `paid_on`, à défaut l'émission. */
    public static function datePaiement(Invoice $invoice): Carbon
    {
        return $invoice->paid_on
            ? Carbon::parse($invoice->paid_on)->startOfDay()
            : SettlementStatement::issuedOn($invoice);
    }

    /* ───────────────────────── une période ───────────────────────────────── */

    /**
     * L'extrait d'une période pour une société, chaque écriture dans la
     * période de sa propre date.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function periode(int $companyId, Carbon $du, Carbon $au, bool $payesSeulement = false): array
    {
        $debut = $du->toDateString();
        $fin = $au->toDateString();
        $lines = [];

        // Relevés ÉMIS dans la période : ventes et compensation.
        $emis = Invoice::where('company_id', $companyId)
            ->whereBetween('issued_on', [$debut, $fin])
            ->when($payesSeulement, fn ($q) => $q->where('status', InvoiceStatus::PAID))
            ->orderBy('issued_on')->orderBy('sequence')->orderBy('id')->get();
        foreach ($emis as $invoice) {
            $lines = array_merge($lines, self::ventesEtCompensation($invoice));
        }

        // Relevés PAYÉS dans la période : le reversement, à sa date. Ceux payés
        // avant que `paid_on` existe n'ont que leur émission pour date.
        $payes = Invoice::where('company_id', $companyId)
            ->where('status', InvoiceStatus::PAID)
            ->where(function ($q) use ($debut, $fin) {
                $q->whereBetween('paid_on', [$debut, $fin])
                  ->orWhere(fn ($r) => $r->whereNull('paid_on')->whereBetween('issued_on', [$debut, $fin]));
            })
            ->orderBy('paid_on')->orderBy('issued_on')->orderBy('sequence')->orderBy('id')->get();
        foreach ($payes as $invoice) {
            $lines = array_merge($lines, self::reversement($invoice));
        }

        if ($payesSeulement) {
            return $lines;
        }

        return array_merge($lines, self::recharges($companyId, $du, $au), self::remises($companyId, $du, $au));
    }

    /**
     * Question 8 — les recharges de portefeuille approuvées : des **avances
     * reçues** du marchand, pas un produit. Datées de l'approbation (c'est elle
     * qui crédite le portefeuille — `WalletRepository::approved()`, seul point
     * de crédit).
     *
     * @return array<int, array<string, string|int>>
     */
    public static function recharges(int $companyId, Carbon $du, Carbon $au): array
    {
        $journals = config('syscohada.journals');
        $lines = [];

        $recharges = Wallet::with('merchant')
            ->where('company_id', $companyId)
            ->where('type', WalletType::INCOME)
            ->where('status', WalletStatus::APPROVED)
            ->whereBetween('updated_at', [$du->copy()->startOfDay(), $au->copy()->endOfDay()])
            ->orderBy('updated_at')->orderBy('id')->get();

        foreach ($recharges as $recharge) {
            $montant = (int) round((float) $recharge->amount);
            if ($montant <= 0) {
                continue;
            }
            $merchant = $recharge->merchant;
            $reference = (string) ($recharge->transaction_id ?: 'REC-' . $recharge->id);
            $line = self::ligne(
                Carbon::parse($recharge->updated_at)->format('Y-m-d'),
                $reference,
                (string) ($merchant?->ifu ?? ''),
                self::auxiliaire($merchant),
            );
            $label = __('statement.journal_wallet_recharge', [
                'reference' => $reference,
                'merchant' => (string) ($merchant?->business_name ?? ''),
            ]);
            $lines[] = $line($journals['bank'], 'bank', $label, $montant, 0);
            $lines[] = $line($journals['bank'], 'wallet_advances', $label, 0, $montant);
        }

        return $lines;
    }

    /**
     * Question 8 — les remises d'espèces des livreurs au hub : le COD collecté
     * qu'ils rapportent transite par un compte de tiers avant la caisse.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function remises(int $companyId, Carbon $du, Carbon $au): array
    {
        $journals = config('syscohada.journals');
        $lines = [];

        $remises = CashReceivedFromDeliveryman::with('deliveryman.user')
            ->where('company_id', $companyId)
            ->whereBetween('date', [$du->copy()->startOfDay(), $au->copy()->endOfDay()])
            ->orderBy('date')->orderBy('id')->get();

        foreach ($remises as $remise) {
            $montant = (int) round((float) $remise->amount);
            if ($montant <= 0) {
                continue;
            }
            $reference = 'REM-' . $remise->id;
            $line = self::ligne(Carbon::parse($remise->date)->format('Y-m-d'), $reference, '', null);
            $label = __('statement.journal_cash_remittance', [
                'reference' => $reference,
                'deliveryman' => (string) ($remise->deliveryman?->user?->name ?? $remise->delivery_man_id),
            ]);
            $lines[] = $line($journals['cash'], 'cash', $label, $montant, 0);
            $lines[] = $line($journals['cash'], 'courier_transit', $label, 0, $montant);
        }

        return $lines;
    }

    /* ───────────────────────── mécanique ─────────────────────────────────── */

    /** Fabrique de lignes : date, pièce et tiers fixés, compte résolu avec son auxiliaire. */
    private static function ligne(string $date, string $piece, string $ifu, ?string $auxiliaire): \Closure
    {
        $accounts = config('syscohada.accounts');

        return function (string $journal, string $account, string $label, int $debit, int $credit) use ($accounts, $date, $piece, $ifu, $auxiliaire): array {
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
    }

    /**
     * Suffixe auxiliaire du marchand, ou `null` si l'on tient un compte collectif.
     *
     * Question 1 de **D2** : auxiliaire par marchand **par défaut** depuis S73.
     * Seuls les comptes de tiers le reçoivent (`syscohada.auxiliarised`) — un
     * produit (7061) ou la TVA (4431) n'a pas de tiers, et le suffixer rendrait
     * la balance illisible.
     */
    private static function auxiliaire(?Merchant $merchant): ?string
    {
        if (config('syscohada.auxiliary') !== 'merchant_code') {
            return null;
        }

        $code = (string) ($merchant?->merchant_unique_id ?? '');
        $code = preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '';

        return $code === '' ? null : strtoupper($code);
    }

    /** Comptes de tiers auxiliarisés ; les autres restent collectifs. */
    private static function compte(string $racine, string $account, ?string $auxiliaire): string
    {
        if ($auxiliaire === null || !in_array($account, (array) config('syscohada.auxiliarised', ['customers', 'cod_liability']), true)) {
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

    /** Contenu CSV des écritures de relevés (séparateur et BOM selon la configuration). */
    public static function csv(iterable $invoices): string
    {
        $lines = [];
        foreach ($invoices as $invoice) {
            $lines = array_merge($lines, self::linesFor($invoice));
        }

        return self::csvLignes($lines);
    }

    /**
     * Contenu CSV de lignes déjà produites (un extrait de période).
     *
     * @param array<int, array<string, string|int>> $lines
     */
    public static function csvLignes(array $lines): string
    {
        $delimiter = (string) config('syscohada.csv.delimiter', ';');
        $handle = fopen('php://temp', 'r+');
        if (config('syscohada.csv.bom', true)) {
            fwrite($handle, "\xEF\xBB\xBF");
        }
        fputcsv($handle, self::COLUMNS, $delimiter);
        foreach ($lines as $row) {
            fputcsv($handle, array_values($row), $delimiter);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}
