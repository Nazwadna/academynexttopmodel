<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsMiddleware
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
        $response = $next($request);
        
        // Don't track admin pages, API, or static assets
        if ($this->shouldTrack($request)) {
            $this->trackVisit($request);
        }
        
        return $response;
    }
    
    /**
     * Determine if the request should be tracked
     */
    private function shouldTrack(Request $request)
    {
        $excludedPaths = [
            'admin',
            'api',
            'assets',
            'css',
            'js',
            'images',
            'fonts'
        ];
        
        $path = $request->path();
        
        foreach ($excludedPaths as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return false;
            }
        }
        
        // Don't track AJAX requests
        if ($request->ajax()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Track website visit
     */
    private function trackVisit(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();
            
            // Get visitor information
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $url = $request->fullUrl();
            $path = $request->path();
            $method = $request->method();
            $referer = $request->header('referer');
            
            // Parse user agent for browser and OS detection
            $browserInfo = $this->parseUserAgent($userAgent);
            
            // Get location from IP (basic implementation)
            $location = $this->getLocationFromIp($ip);
            
            // Check if it's a unique visitor today
            $isUnique = !$this->hasVisitedToday($ip, $today);
            
            // Store visit data
            DB::table('analytics_visits')->insert([
                'date' => $today,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'url' => $url,
                'path' => $path,
                'method' => $method,
                'referer' => $referer,
                'browser' => $browserInfo['browser'],
                'browser_version' => $browserInfo['version'],
                'os' => $browserInfo['os'],
                'device_type' => $browserInfo['device'],
                'country' => $location['country'],
                'city' => $location['city'],
                'is_unique' => $isUnique,
                'created_at' => Carbon::now()
            ]);
            
            // Update daily statistics
            $this->updateDailyStats($today, $isUnique);
            
        } catch (\Exception $e) {
            // Fail silently to not break the application
            \Log::error('Analytics tracking failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse user agent string
     */
    private function parseUserAgent($userAgent)
    {
        $browser = 'Unknown';
        $version = 'Unknown';
        $os = 'Unknown';
        $device = 'Desktop';
        
        // Browser detection
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome';
            $version = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox';
            $version = $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Safari';
            $version = $matches[1];
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Edge';
            $version = $matches[1];
        }
        
        // OS detection
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X ([0-9._]+)/', $userAgent, $matches)) {
            $os = 'macOS';
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Android';
            $device = 'Mobile';
        } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'iOS';
            $device = 'Mobile';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        }
        
        // Device detection
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $device = 'Tablet';
        }
        
        return [
            'browser' => $browser,
            'version' => $version,
            'os' => $os,
            'device' => $device
        ];
    }
    
    /**
     * Get location from IP (basic implementation)
     */
    private function getLocationFromIp($ip)
    {
        // For demo purposes, return default location
        // In production, use a proper IP geolocation service
        return [
            'country' => 'Unknown',
            'city' => 'Unknown'
        ];
        
        // Example with IP API (uncomment and implement):
        /*
        $response = file_get_contents("http://ip-api.com/json/{$ip}");
        $data = json_decode($response, true);
        
        return [
            'country' => $data['country'] ?? 'Unknown',
            'city' => $data['city'] ?? 'Unknown'
        ];
        */
    }
    
    /**
     * Check if visitor has already visited today
     */
    private function hasVisitedToday($ip, $date)
    {
        return DB::table('analytics_visits')
            ->where('ip_address', $ip)
            ->where('date', $date)
            ->exists();
    }
    
    /**
     * Update daily statistics
     */
    private function updateDailyStats($date, $isUnique)
    {
        $stats = DB::table('analytics_daily')
            ->where('date', $date)
            ->first();
        
        if ($stats) {
            DB::table('analytics_daily')
                ->where('date', $date)
                ->update([
                    'visits' => DB::raw('visits + 1'),
                    'unique_visits' => DB::raw('unique_visits + ' . ($isUnique ? '1' : '0')),
                    'updated_at' => Carbon::now()
                ]);
        } else {
            DB::table('analytics_daily')->insert([
                'date' => $date,
                'visits' => 1,
                'unique_visits' => $isUnique ? 1 : 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
