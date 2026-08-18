{{-- Retour de la page FedaPay. Cette vue N'ACCORDE RIEN : elle informe.
     Le crédit du wallet appartient au webhook signé. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('fedapay.title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #F7F9F8; color: #1A1A1A;
               display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; border: 1px solid #E1E6E3; border-radius: 16px;
                padding: 32px; max-width: 420px; text-align: center; }
        h1 { color: #12503A; font-size: 20px; margin: 0 0 12px; }
        p { color: #5F6B66; line-height: 1.5; margin: 0 0 8px; }
        .ref { font-family: ui-monospace, monospace; color: #12503A; }
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
