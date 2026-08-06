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


    public function render($request, Throwable $e)
    {

        // dd($e->getPrevious());


        if($this->isHttpException($e)){

            if($e->getStatusCode()       == 401){

                return response()->view('errors.401');
            }elseif($e->getStatusCode()   == 404){

                return response()->view('errors.404');

            }elseif($e->getStatusCode()  == 403){

                return response()->view('errors.403');

            }elseif($e->getStatusCode() == 405){

                return response()->view('errors.405');

            }elseif($e->getStatusCode() == 419){

                return response()->view('errors.419');

            }elseif($e->getStatusCode() == 429){

                return response()->view('errors.429');

            }elseif($e->getStatusCode() == 500){

                return response()->view('errors.500');

            }
        }else{
           return  parent::render($request,$e);
        }
    }
}
