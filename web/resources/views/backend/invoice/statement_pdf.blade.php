{{-- Relevé de règlement (PDF) — chantier 4. Rendu par dompdf à partir de
     App\Services\Invoicing\SettlementStatement : aucun calcul ici. --}}
@php($s = $statement)
@php($fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' FCFA')
@php($or = fn ($v) => $v !== '' ? $v : __('statement.missing'))
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ __('statement.title') }} {{ $s['number'] }}</title>
    <style>
        /* Charte BeninLink. ⚠️ Les couleurs ci-dessous sont une COPIE de
           public/beninlink/css/tokens.css — dompdf ne résout pas var(), donc
           elles ne peuvent pas y être référencées. Correspondances :
             #12503A = --bl-primary      #1A1A1A = --bl-text
             #5F6B66 = --bl-text-muted   #E1E6E3 = --bl-border
             #F7F9F8 = --bl-background
           Toute retouche de la charte doit repasser ici. */
        @page { margin: 22mm 16mm 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1A1A1A; margin: 0; }
        h1 { font-size: 18px; color: #12503A; margin: 0 0 2px; }
        .subtitle { color: #5F6B66; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        .parties td { vertical-align: top; width: 50%; padding: 0 8px 0 0; }
        .box { border: 1px solid #E1E6E3; border-radius: 4px; padding: 8px 10px; }
        .box h2 { font-size: 11px; color: #12503A; margin: 0 0 6px; text-transform: uppercase; letter-spacing: .04em; }
        .box p { margin: 0 0 2px; }
        .meta { margin: 10px 0 14px; }
        .meta td { padding: 3px 8px; border: 1px solid #E1E6E3; }
        .meta td.k { background: #F7F9F8; font-weight: bold; width: 22%; }
        .lines th { background: #12503A; color: #fff; padding: 5px 4px; font-size: 9px; text-align: left; }
        .lines td { padding: 4px; border-bottom: 1px solid #E1E6E3; font-size: 9px; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 58%; margin-left: 42%; margin-top: 10px; }
        .totals td { padding: 4px 8px; border-bottom: 1px solid #E1E6E3; }
        .totals td.k { color: #5F6B66; }
        .totals tr.net td { font-weight: bold; font-size: 12px; color: #12503A; border-top: 2px solid #12503A; border-bottom: none; }
        .note { color: #5F6B66; margin-top: 8px; }
        .warn { color: #B42318; font-weight: bold; margin-top: 8px; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 8px; color: #5F6B66; border-top: 1px solid #E1E6E3; padding-top: 4px; }
    </style>
</head>
<body>
    <h1>{{ __('statement.title') }} {{ __('statement.number') }} {{ $s['number'] }}</h1>
    <p class="subtitle">{{ __('statement.subtitle') }}</p>

    <table class="parties">
        <tr>
            <td>
                <div class="box">
                    <h2>{{ __('statement.carrier') }}</h2>
                    <p><strong>{{ $s['carrier']['name'] }}</strong></p>
                    <p>{{ __('statement.ifu') }} : {{ $or($s['carrier']['ifu']) }}</p>
                    <p>{{ __('statement.rccm') }} : {{ $or($s['carrier']['rccm']) }}</p>
                    <p>{{ __('statement.address') }} : {{ $or($s['carrier']['address']) }}</p>
                    <p>{{ __('statement.phone') }} : {{ $or($s['carrier']['phone']) }}
                        @if($s['carrier']['email'] !== '') · {{ $s['carrier']['email'] }} @endif</p>
                </div>
            </td>
            <td>
                <div class="box">
                    <h2>{{ __('statement.merchant') }}</h2>
                    <p><strong>{{ $s['merchant']['name'] }}</strong>
                        @if($s['merchant']['code'] !== '') ({{ __('statement.merchant_code') }} {{ $s['merchant']['code'] }}) @endif</p>
                    <p>{{ __('statement.ifu') }} : {{ $or($s['merchant']['ifu']) }}</p>
                    <p>{{ __('statement.rccm') }} : {{ $or($s['merchant']['rccm']) }}</p>
                    <p>{{ __('statement.address') }} : {{ $or($s['merchant']['address']) }}</p>
                    <p>{{ __('statement.phone') }} : {{ $or($s['merchant']['phone']) }}</p>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="k">{{ __('statement.issued_on') }}</td><td>{{ $s['issued_on']->format('d/m/Y') }}</td>
            <td class="k">{{ __('statement.fiscal_year') }}</td><td>{{ $s['fiscal_year'] }}</td>
        </tr>
        <tr>
            <td class="k">{{ __('statement.period') }}</td><td>{{ $s['period']['from'] }} – {{ $s['period']['to'] }}</td>
            <td class="k">{{ __('statement.status') }}</td><td>{{ $s['status_label'] }}</td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('statement.col_date') }}</th>
                <th>{{ __('statement.col_tracking') }}</th>
                <th>{{ __('statement.col_customer') }}</th>
                <th>{{ __('statement.col_status') }}</th>
                <th class="num">{{ __('statement.col_collected') }}</th>
                <th class="num">{{ __('statement.col_delivery_fee') }}</th>
                <th class="num">{{ __('statement.col_cod_fee') }}</th>
                <th class="num">{{ __('statement.col_return_fee') }}</th>
                <th class="num">{{ __('statement.col_vat') }}</th>
                <th class="num">{{ __('statement.col_net') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($s['lines'] as $line)
                <tr>
                    <td>{{ $line['date'] }}</td>
                    <td>{{ $line['tracking_id'] }}</td>
                    <td>{{ $line['customer_name'] }}</td>
                    <td>{{ $line['status'] }}</td>
                    <td class="num">{{ number_format($line['collected'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($line['delivery_fee'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($line['cod_fee'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($line['return_fee'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($line['vat'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($line['net'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="k">{{ __('statement.total_collected') }}</td><td class="num">{{ $fmt($s['totals']['collected']) }}</td></tr>
        <tr><td class="k">{{ __('statement.total_fees_ht') }}</td><td class="num">− {{ $fmt($s['totals']['fees_ht']) }}</td></tr>
        <tr><td class="k">{{ __('statement.total_vat') }}
            @if($s['vat_rates']) ({{ implode(' %, ', $s['vat_rates']) }} %) @endif</td>
            <td class="num">− {{ $fmt($s['totals']['vat']) }}</td></tr>
        <tr><td class="k">{{ __('statement.total_fees_ttc') }}</td><td class="num">{{ $fmt($s['totals']['fees_ttc']) }}</td></tr>
        <tr class="net"><td>{{ __('statement.total_net') }}</td><td class="num">{{ $fmt($s['totals']['net']) }}</td></tr>
    </table>

    <p class="note">{{ __('statement.formula') }} {{ __('statement.amounts_in') }}
        @if(!$s['vat_rates']) {{ __('statement.vat_none') }} @endif</p>
    @unless($s['totals']['consistent'])
        <p class="warn">{{ __('statement.inconsistent', ['computed' => $fmt($s['totals']['net']), 'recorded' => $fmt($s['totals']['net_recorded'])]) }}</p>
    @endunless

    <div class="footer">
        {{ __('statement.legal_footer', ['name' => $s['carrier']['name'], 'ifu' => $or($s['carrier']['ifu']), 'rccm' => $or($s['carrier']['rccm']), 'address' => $or($s['carrier']['address'])]) }}
        — {{ __('statement.generated_at', ['date' => now()->format('d/m/Y H:i')]) }}
    </div>
</body>
</html>
