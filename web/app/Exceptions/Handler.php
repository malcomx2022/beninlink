<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    // public function register()
    // {

    // }

    public function report(Throwable $e)
    {
        parent::report($e);
    }


    /**
     * S15 — le socle rendait ici `errors.<code>` pour CHAQUE HttpException...
     * avec un statut HTTP 200 (`response()->view()` sans code). Consequences :
     *
     *   - une API qui repond 200 + une page HTML sur un 404, un 403 ou un 405 ;
     *     un client mobile ne pouvait plus distinguer un succes d'un echec ;
     *   - des erreurs invisibles pour toute supervision qui compte les 5xx.
     *
     * Laravel choisit deja `resources/views/errors/<code>.blade.php` quand la vue
     * existe, en conservant le vrai code, et repond en JSON aux requetes d'API.
     * Les sept branches d'origine ne faisaient donc qu'ajouter un bug : elles sont
     * supprimees, le comportement du framework suffit.
     */
}
