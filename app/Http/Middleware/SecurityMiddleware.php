<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Security Headers
        $response = $next($request);
        
        // Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Strict Transport Security (only in production)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://api.whatsapp.com; " .
               "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Rate limiting logging
        $this->logSuspiciousActivity($request);
        
        return $response;
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request)
    {
        $suspiciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/\.\./',
            '/union.*select/i',
            '/drop.*table/i',
            '/insert.*into/i',
            '/delete.*from/i'
        ];
        
        $input = json_encode($request->all());
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning('Suspicious activity detected', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'input' => $request->except(['password', 'password_confirmation'])
                ]);
                break;
            }
        }
    }
}
