<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckVerifiedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->is_verified) {
            auth()->logout();
            return redirect('/login')->with('error', 'Akun Anda belum terverifikasi. Silakan cek WhatsApp untuk kode OTP.');
        }

        return $next($request);
    }
}
