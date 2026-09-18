{{-- Retour de la page FedaPay. Cette vue N'ACCORDE RIEN : elle informe.
     Le crédit du wallet appartient au webhook signé. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('fedapay.title') }}</title>
    {{-- Charte BeninLink : couleurs et polices viennent des jetons. --}}
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/tokens.css') }}">
    <style>
        body { font-family: var(--bl-font-body); background: var(--bl-background); color: var(--bl-text);
               display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: var(--bl-surface); border: 1px solid var(--bl-border);
                border-radius: var(--bl-radius-lg); padding: var(--bl-space-xl); max-width: 420px; text-align: center; }
        h1 { color: var(--bl-primary); font-family: var(--bl-font-heading);
             font-weight: var(--bl-weight-heading); font-size: var(--bl-size-xl); margin: 0 0 12px; }
        p { color: var(--bl-text-muted); line-height: 1.5; margin: 0 0 8px; }
        /* La référence est un identifiant : chiffres en Sora, chasse fixe. */
        .ref { font-family: var(--bl-font-numeric); color: var(--bl-primary); letter-spacing: .02em; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('fedapay.title') }}</h1>
        <p>{{ __('fedapay.pending_notice') }}</p>
        @if($reference)
            <p class="ref">{{ $reference }}</p>
        @endif
    </div>
</body>
</html>
