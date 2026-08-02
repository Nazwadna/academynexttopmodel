<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Pastikan user login dan role admin (sama pola dengan /admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        if ($user->role !== 'admin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            return redirect('/');
        }

        return $next($request);
    }
}
