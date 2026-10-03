{{--
    Courriel d'envoi d'un relevé de règlement (`App\Mail\InvoicePDFSend`).

    Le relevé est en pièce jointe (PDF du chantier 4) ; ce gabarit n'en reprend
    que l'essentiel. Même ossature en tables que `backend/merchant/mail/feed.blade.php`.

    ⚠️ Le nom du fichier porte une majuscule — héritage du socle, conservé (0 fichier
    supprimé) : la vue s'appelle `backend.merchant.invoice.Invoice_mail_pdf`, et un
    serveur Linux y tient, là où le poste de développement de l'éditeur (casse
    insensible) laissait passer `invoice_mail_pdf` (S69).

    ⚠️ Aucune lecture de `settings()` ici (F4) : la marque est passée par le mailable.
    Le montant se met en forme comme dans `statement_pdf` — `formatAmount()` lirait la
    devise de la société ambiante.
--}}
@php($fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' FCFA')
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('statement.title') }} {{ __('statement.number') }} {{ $statement['number'] }}</title>
    <style>
        body { background-color: aliceblue; color: #8094ae; }
        a { color: #12503A; }
    </style>
</head>
<body style="margin: 10;">
    @if(filled($companyLogo))
        <table style="width:100%;max-width:650px;margin:auto;">
            <tr>
                <td style="text-align:center;padding:30px 10px">
                    <img alt="{{ $companyName }}" src="{{ static_asset($companyLogo) }}" style="height:50px;"/>
                </td>
            </tr>
        </table>
    @endif

    <table style="width:100%;max-width:650px;margin:auto;background-color:white;">
        <tr>
            <td style="padding:30px;line-height:1.6;">
                <p>Bonjour <b style="font-style:italic;">{{ $destinataire }}</b>,</p>

                <p style="color:#12503A;font-weight:bold;font-size:17px;margin-bottom:6px;">
                    {{ __('statement.title') }} {{ __('statement.number') }} {{ $statement['number'] }}
                </p>
                <p style="margin-top:0;">
                    {{ __('statement.mail_intro', ['date' => $statement['issued_on']->format('d/m/Y')]) }}
                </p>

                <table style="margin-top:16px;border-collapse:collapse;">
                    <tr>
                        <td style="padding:4px 12px 4px 0;"><b>{{ __('statement.period') }}</b></td>
                        <td style="padding:4px 0;">{{ $statement['period']['from'] }} – {{ $statement['period']['to'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 12px 4px 0;"><b>{{ __('statement.total_net') }}</b></td>
                        <td style="padding:4px 0;color:#12503A;font-weight:bold;">{{ $fmt($statement['totals']['net_recorded']) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 12px 4px 0;"><b>{{ __('statement.status') }}</b></td>
                        <td style="padding:4px 0;">{{ $statement['status_label'] }}</td>
                    </tr>
                </table>

                <p style="margin-top:20px;">{{ __('statement.mail_attachment') }}</p>

                @if(filled($courriel) || filled($telephone))
                    <p style="font-size:13px;">
                        {{ __('notification.mail_contact') }}
                        @if(filled($courriel))<a href="mailto:{{ $courriel }}">{{ $courriel }}</a>@endif
                        @if(filled($courriel) && filled($telephone)) — @endif
                        @if(filled($telephone)){{ $telephone }}@endif
                    </p>
                @endif
            </td>
        </tr>
    </table>

    @if(filled($mentions))
        <table style="width:100%;max-width:650px;margin:auto;">
            <tr>
                <td style="text-align:center;padding:20px 10px;font-size:12px;">{{ $mentions }}</td>
            </tr>
        </table>
    @endif
</body>
</html>
