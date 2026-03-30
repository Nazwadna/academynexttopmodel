<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LocaleMiddleware
{
    public function handle($request, Closure $next)
    {
        $locale = session('locale');
        Log::info("LocaleMiddleware called, locale from session: " . $locale);
        
        if ($locale && in_array($locale, ['en', 'id'])) {
            app()->setLocale($locale);
            Log::info("Locale set to: " . app()->getLocale());
        } else {
            // Set default locale if none is set
            app()->setLocale('en');
            Log::info("Default locale set to: " . app()->getLocale());
        }

        return $next($request);
    }
}
