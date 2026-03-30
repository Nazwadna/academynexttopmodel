<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function () {
            if (Session::has('locale')) {
                App::setLocale(Session::get('locale'));
                \Log::info('🌐 Locale diset DI View::composer: ' . Session::get('locale'));
                \Log::info('🌐 Locale di-set: ' . App::getLocale());

            }
        });
    }

}
