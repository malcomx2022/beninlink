<?php

namespace App\Providers;

use App\Http\ViewComposer\PlanComposer;
use App\Http\ViewComposer\ServiceComposer;
use App\Http\ViewComposer\SocialLinkComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
     
        View::composer('frontend.layouts.footer', ServiceComposer::class);
        View::composer('frontend.layouts.footer', SocialLinkComposer::class);
        View::composer('frontend.layouts.footer', PlanComposer::class);
        View::composer('frontend.layouts.navbar', SocialLinkComposer::class);
    }
}
