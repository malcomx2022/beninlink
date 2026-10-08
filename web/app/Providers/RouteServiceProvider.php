<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route; 

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';
    // public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();
         
            $this->routes(function () { 
                Route::prefix('api')
                    ->middleware('api')
                    ->group(base_path('routes/api.php'));
  
                Route::middleware('web')  
                    ->group(base_path('routes/web.php')); 
              
                Route::middleware('web')
                ->group(base_path('routes/superadmin.php')); 
            });

    }

    protected function centralDomains(): array
    {
        return config('tenancy.central_domains');
    }
    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // S96 — les points d'entrée d'authentification de l'API sont limités contre
        // la force brute, comme le login web (`ThrottlesLogins`, 5 essais). Deux
        // bornes : 5 par minute sur le COUPLE identifiant + adresse (un compte visé),
        // 30 par minute par adresse (une adresse qui énumère des comptes). La seconde
        // ne bloque pas une agence entière derrière un NAT à la première faute de
        // frappe, la première ne laisse pas tourner un dictionnaire. La réponse garde
        // l'enveloppe de l'API et parle français, dans la locale négociée (S90).
        RateLimiter::for('connexion', function (Request $request) {
            // `merchant_id` (signin), `driver_id` (deliveryman/login), `email` (réinitialisation), `mobile` (OTP).
            $identifiant = strtolower(trim((string) ($request->input('merchant_id')
                ?? $request->input('driver_id')
                ?? $request->input('email')
                ?? $request->input('mobile')
                ?? $request->input('unique_id')
                ?? '')));
            // ⚠️ `ThrottleRequests` est dans `$middlewarePriority` : il tourne AVANT `ApiLocale`
            // (S90). La langue se négocie donc ici, par la même règle.
            $refus = function (Request $r, array $headers) {
                $langue = \App\Http\Middleware\ApiLocale::negocier((string) $r->header('Accept-Language', ''), array_keys(config('locales.supported', [])))
                    ?? app()->getLocale();

                return response()->json([
                    'success' => false,
                    'message' => __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60], $langue),
                    'data'    => [],
                ], 429, $headers);
            };

            return [
                Limit::perMinute(5)->by('connexion:' . $identifiant . '|' . $request->ip())->response($refus),
                Limit::perMinute(30)->by('connexion-ip:' . $request->ip())->response($refus),
            ];
        });

        // S130 — les mêmes bornes pour le parcours OTP du SITE marchand (`merchant/otp-verification`,
        // `merchant/resend-otp`). S96 n'avait limité que l'API : sur le web, un code à cinq chiffres
        // (90 000 possibilités) se devinait sans frein, et le renvoi envoyait un SMS payant à chaque
        // clic. Réponse d'écran : retour au formulaire avec le message, pas une enveloppe JSON.
        RateLimiter::for('connexion-web', function (Request $request) {
            $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile', ''));
            $refus = fn (Request $r, array $headers) => redirect()->route('merchant.otp-verification-form')
                ->with('warning', __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60]));

            return [
                Limit::perMinute(5)->by('connexion-web:' . $mobile . '|' . $request->ip())->response($refus),
                Limit::perMinute(30)->by('connexion-web-ip:' . $request->ip())->response($refus),
            ];
        });
    }
}
