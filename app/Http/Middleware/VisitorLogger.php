<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class VisitorLogger
{
    /**
     * Catat visitor di homepage tanpa pernah menggagalkan response.
     */
    public function handle(Request $request, Closure $next)
    {
        // Selalu lanjutkan request dulu — logging bersifat best-effort
        $response = $next($request);

        try {
            $this->logHomepageVisit($request);
        } catch (\Throwable $e) {
            Log::warning('VisitorLogger skipped: '.$e->getMessage());
        }

        return $response;
    }

    private function logHomepageVisit(Request $request): void
    {
        $path = $request->path();
        if ($path !== '/' && $path !== '') {
            return;
        }

        foreach (['admin', 'addmodel', 'editmodel', 'editprofile'] as $ex) {
            if (stripos($path, $ex) !== false) {
                return;
            }
        }

        if (!$this->databaseReady()) {
            return;
        }

        $user = Auth::user();
        if ($user && ($user->role ?? null) === 'admin') {
            return;
        }

        $sessionId = Session::getId();
        if ($sessionId === '') {
            return;
        }

        DB::table('visitor')->updateOrInsert(
            ['session_id' => $sessionId],
            [
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'visited_at' => now(),
                'last_activity' => now(),
                'is_online' => 1,
                'user_id' => $user->id_user ?? null,
            ]
        );

        if ($user && isset($user->id_user)) {
            DB::table('users')
                ->where('id_user', $user->id_user)
                ->update(['last_active' => now()]);
        }
    }

    private function databaseReady(): bool
    {
        try {
            DB::connection()->getPdo();
            return Schema::hasTable('visitor');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
