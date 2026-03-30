<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class VisitorLogger
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
        $excluded = ['admin', 'addmodel', 'editmodel', 'editprofile'];
        $path = $request->path();
        
        foreach ($excluded as $ex) {
            if (stripos($path, $ex) !== false) {
                return $next($request);
            }
        }

        // Hanya catat jika user mengakses website utama (halaman utama)
        if ($path === '/' || $path === '') {
            $sessionId = Session::getId();
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();
            
            // Cek apakah user yang login adalah admin
            $user = Auth::user();
            $isAdmin = $user && $user->role === 'admin';
            
            // Hanya catat jika bukan admin
            if (!$isAdmin) {
                // Update atau insert record untuk user yang sedang online
                DB::table('visitor')->updateOrInsert(
                    [
                        'session_id' => $sessionId,
                        'ip_address' => $ipAddress
                    ],
                    [
                        'user_agent' => $userAgent,
                        'visited_at' => now(),
                        'last_activity' => now(),
                        'is_online' => 1,
                        'user_id' => $user ? $user->id_user : null
                    ]
                );
                // Update last_active di tabel users
                if ($user) {
                    DB::table('users')->where('id_user', $user->id_user)->update(['last_active' => now()]);
                }
            }
        }

        return $next($request);
    }
}