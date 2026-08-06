<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use DB;
class IsNotInstalledMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $th) {
             return response()->view('installer.index');
        }

        if(Config::get('app.app_installed') == 'yes'  && Schema::hasTable('settings') && Schema::hasTable('general_settings') && Schema::hasTable('users')):
            return redirect('/');
        endif;

        return $next($request);
    }
}
