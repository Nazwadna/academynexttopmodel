<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\GoogleProvider;
use Illuminate\Support\Facades\Session;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');
    
        return $driver->with(['prompt' => 'select_account'])->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = $googleUser->getEmail();

            // Cek apakah user sudah terdaftar
            $user = User::where('email', $email)->first();

            if (!$user) {
                // User belum terdaftar, arahkan ke halaman register dengan email yang sudah diisi
                Session::put('google_email', $email);
                Session::put('google_name', $googleUser->getName());
                
                return redirect('/register')->with('google_data', [
                    'email' => $email,
                    'name' => $googleUser->getName()
                ]);
            } else {
                // User sudah terdaftar, arahkan ke halaman login dengan email yang sudah diisi
                Session::put('google_email', $email);
                
                return redirect('/login')->with('google_data', [
                    'email' => $email
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return redirect('/login')->with('error', 'Gagal login dengan Google.');
        }
    }
}