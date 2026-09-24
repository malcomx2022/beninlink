{{--
    Courriel d'une entrée du fil marchand (`App\Mail\MerchantFeedMail`).

    Même ossature en tables que `signup.blade.php` — c'est la mise en page que
    les clients de messagerie rendent sans surprise, et celle que le dépôt
    utilise déjà. Les textes arrivent rédigés : ce gabarit ne compose rien.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre }}</title>
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

                <p style="color:#12503A;font-weight:bold;font-size:17px;margin-bottom:6px;">{{ $titre }}</p>
                <p style="margin-top:0;">{{ $corps }}</p>

                @if(filled($extra['tracking_id'] ?? null))
                    <p style="margin-top:20px;">
                        <span style="display:inline-block;width:110px;"><b>{{ __('parcel.tracking_id') }}</b></span>
                        <span>: {{ $extra['tracking_id'] }}</span>
                    </p>
                @endif

                <p style="margin-top:24px;">{{ __('notification.mail_outro') }}</p>

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
                <td style="padding:10px 30px;text-align:center;">
                    <p style="font-size:13px;">{{ $mentions }}</p>
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
