<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Services\OpenApi\SpecGenerator;

/**
 * Sert la spécification OpenAPI et sa page de consultation (Swagger UI).
 *
 * Public et sans enveloppe : c'est un document, pas une ressource métier.
 * La spécification est régénérée à la demande depuis le routeur, ce qui la
 * garde exacte même si `openapi:generate` n'a pas été relancé.
 */
class OpenApiController extends Controller
{
    public function spec(SpecGenerator $generator)
    {
        return response()->json($generator->generate(), 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function docs()
    {
        return view('api.docs', ['specUrl' => route('openapi.spec')]);
    }
}
