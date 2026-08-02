<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Opsional: tandai visitor offline.
 * Jangan daftarkan di route homepage — bisa dipanggil manual/schedule.
 */
class CleanupInactiveUsers
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public static function markInactiveSafely(): void
    {
        try {
            DB::connection()->getPdo();
            if (!Schema::hasTable('visitor')) {
                return;
            }

            DB::table('visitor')
                ->where('last_activity', '<', now()->subMinutes(30))
                ->where('is_online', 1)
                ->update(['is_online' => 0]);
        } catch (\Throwable $e) {
            Log::warning('CleanupInactiveUsers skipped: '.$e->getMessage());
        }
    }
}
