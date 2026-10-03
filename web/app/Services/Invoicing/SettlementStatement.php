<?php

namespace App\Services\Invoicing;

use App\Enums\InvoiceStatus;
use App\Enums\VatStatus;
use App\Services\Parcel\VatRate;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchantpanel\Invoice;
use Carbon\Carbon;

/**
 * Relevé de règlement d'un marchand, sous forme de données prêtes à rendre.
 *
 * « Facture » marchand = relevé de règlement (`.claude/rules/syscohada.md`) :
 *     Encaissé COD − Frais (HT) − TVA = Net à reverser.
 *
 * Une seule source pour le PDF, le CSV et l'export journal : les trois
 * lisent ce tableau, aucun ne recalcule de son côté. Les montants sortent en
 * **entiers XOF**. Les lignes viennent de `invoice_parcels`, figées à
 * l'émission, jamais des colis vivants — un colis modifié après coup ne
 * change pas un relevé déjà remis.
 *
 * La société et le marchand sont lus **explicitement** par identifiant :
 * ce service tourne aussi hors requête (lien signé, commande), où
 * `settings()` retomberait sur la société 1.
 */
class SettlementStatement
{
    public static function for(Invoice $invoice): array
    {
        $company = GeneralSettings::find($invoice->company_id);
        $merchant = $invoice->merchant;
        $issuedOn = self::issuedOn($invoice);

        $lines = [];
        $dates = [];
        $rates = [];

        foreach ($invoice->invoiceParcels()->with('parcel')->orderBy('id')->get() as $item) {
            $parcel = $item->parcel;
            $vat = self::int($item->vat_amount);
            $ttc = self::int($item->total_charge_amount);
            $date = $parcel?->delivery_date ?: $parcel?->updated_at;
            if ($date) {
                $dates[] = Carbon::parse($date);
            }
            if ($parcel && (float) $parcel->vat > 0) {
                $rates[(string) (0 + $parcel->vat)] = true;
            }

            $lines[] = [
                'tracking_id' => $parcel?->tracking_id ?? '',
                'customer_name' => $parcel?->customer_name ?? '',
                'date' => $date ? Carbon::parse($date)->format('d/m/Y') : '',
                'status' => trans('parcelStatus.' . $item->parcel_status),
                'collected' => self::int($item->collected_amount),
                'delivery_fee' => self::int($item->total_delivery_amount),
                'cod_fee' => self::int($item->cod_amount),
                'return_fee' => self::int($item->return_charge),
                'fees_ht' => $ttc - $vat,
                'vat' => $vat,
                'fees_ttc' => $ttc,
                'net' => self::int($item->current_payable),
            ];
        }

        $totals = [
            'collected' => array_sum(array_column($lines, 'collected')),
            'delivery_fee' => array_sum(array_column($lines, 'delivery_fee')),
            'cod_fee' => array_sum(array_column($lines, 'cod_fee')),
            'return_fee' => array_sum(array_column($lines, 'return_fee')),
            'fees_ht' => array_sum(array_column($lines, 'fees_ht')),
            'vat' => array_sum(array_column($lines, 'vat')),
            'fees_ttc' => array_sum(array_column($lines, 'fees_ttc')),
        ];
        $totals['net'] = $totals['collected'] - $totals['fees_ttc'];
        // Ce que la facture porte en base ; un écart signale une reprise à faire.
        $totals['net_recorded'] = self::int($invoice->current_payable);
        $totals['consistent'] = $totals['net'] === $totals['net_recorded'];

        sort($dates);

        return [
            'number' => (string) $invoice->invoice_id,
            'issued_on' => $issuedOn,
            'fiscal_year' => (int) ($invoice->fiscal_year ?: $issuedOn->format('Y')),
            'status' => (int) $invoice->status,
            'status_label' => self::statusLabel((int) $invoice->status),
            'period' => [
                'from' => $dates ? $dates[0]->format('d/m/Y') : $issuedOn->format('d/m/Y'),
                'to' => $dates ? end($dates)->format('d/m/Y') : $issuedOn->format('d/m/Y'),
            ],
            'carrier' => [
                'name' => (string) ($company?->name ?? ''),
                'ifu' => (string) ($company?->ifu ?? ''),
                'rccm' => (string) ($company?->rccm ?? ''),
                'address' => (string) ($company?->address ?? ''),
                'phone' => (string) ($company?->phone ?? ''),
                'email' => (string) ($company?->email ?? ''),
            ],
            'merchant' => [
                'name' => (string) ($merchant?->business_name ?? ''),
                'code' => (string) ($merchant?->merchant_unique_id ?? ''),
                'ifu' => (string) ($merchant?->ifu ?? ''),
                'rccm' => (string) ($merchant?->rccm ?? ''),
                'address' => (string) ($merchant?->address ?? ''),
                'phone' => (string) ($merchant?->user?->mobile ?? ''),
                // R7 b (S75) : exonéré ≠ non renseigné, et le document le dit.
                'vat_status' => $merchant ? VatRate::statut($merchant) : VatStatus::UNSET,
                'vat_exempt' => $merchant ? VatRate::estExonere($merchant) : false,
            ],
            // Les clés numériques d'un tableau PHP redeviennent des entiers : on
            // renvoie des chaînes, telles qu'elles s'affichent (« 18 », « 5.5 »).
            'vat_rates' => array_map('strval', array_keys($rates)),
            'lines' => $lines,
            'totals' => $totals,
            'currency' => 'XOF',
        ];
    }

    /** Date d'émission : `issued_on` si renseignée, sinon la chaîne `d-m-Y` du socle. */
    public static function issuedOn(Invoice $invoice): Carbon
    {
        if ($invoice->issued_on) {
            return Carbon::parse($invoice->issued_on)->startOfDay();
        }
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, (string) $invoice->invoice_date)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($invoice->created_at)->startOfDay();
    }

    private static function statusLabel(int $status): string
    {
        return match ($status) {
            InvoiceStatus::PAID => __('invoice.' . InvoiceStatus::PAID),
            InvoiceStatus::UNPAID => __('invoice.' . InvoiceStatus::UNPAID),
            InvoiceStatus::PROCESSING => __('invoice.' . InvoiceStatus::PROCESSING),
            default => '',
        };
    }

    /** FCFA : entier, le franc CFA n'a pas de subdivision. */
    public static function int(mixed $value): int
    {
        return (int) round((float) ($value ?? 0));
    }
}
