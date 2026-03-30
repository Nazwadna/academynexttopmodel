<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CleanupInactiveUsers
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
        // Bersihkan user yang tidak aktif (lebih dari 30 menit)
        DB::table('visitor')
            ->where('last_activity', '<', now()->subMinutes(30))
            ->update(['is_online' => 0]);

        return $next($request);
    }
} 