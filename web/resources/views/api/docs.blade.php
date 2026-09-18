{{-- Page de consultation de l'API (Swagger UI, chantier 7). La spécification
     vient de {{ $specUrl }}, générée depuis le routeur. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('openapi.title') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    {{-- Charte BeninLink : la couleur vient du jeton, plus d'un hexadécimal recopié. --}}
    <link rel="stylesheet" href="{{ static_asset('beninlink/css/tokens.css') }}">
    <style>
        body { margin: 0; }
        .topbar { display: none; }
        .swagger-ui .info .title { color: var(--bl-primary); font-family: var(--bl-font-heading); }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: @json($specUrl),
                dom_id: '#swagger-ui',
                deepLinking: true,
                docExpansion: 'list',
                defaultModelsExpandDepth: 1,
                persistAuthorization: true,
            });
        };
    </script>
</body>
</html>
