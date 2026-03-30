<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otp;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\Talent;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        \Log::info('Registration attempt started', [
            'data' => $request->all(),
            'ip' => $request->ip()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'number_phone' => 'required|string|max:20',
            'role' => 'required|in:client,model',
            'pin' => 'nullable|digits:6',
            'terms' => 'required|accepted',
        ], [
            'terms.required' => 'You must agree to the terms and conditions',
            'terms.accepted' => 'You must agree to the terms and conditions',
        ]);

        if ($validator->fails()) {
            \Log::warning('Registration validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        \Log::info('Registration validation passed');

        // Jika role model, validasi PIN dan nama model
        if ($request->role === 'model') {
            \Log::info('Processing model registration');
            // Wajib isi PIN
            $validator = Validator::make($request->all(), [
                'pin' => 'required|digits:6'
            ], [
                'pin.required' => 'PIN wajib diisi untuk registrasi sebagai model',
                'pin.digits' => 'PIN harus 6 digit'
            ]);
            if ($validator->fails()) {
                \Log::warning('Model PIN validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Pastikan nama ada di tabel models
            $talent = Talent::where('nama_model', $request->name)->first();
            if (!$talent) {
                \Log::warning('Model name not found in talents table', [
                    'name' => $request->name
                ]);
                return redirect()->back()
                    ->withErrors(['name' => 'Nama model tidak ditemukan. Gunakan nama yang sesuai di daftar model.'])
                    ->withInput();
            }

            // Temukan akun user model pre-generated berdasarkan nama
            $existingUser = User::where('name', $request->name)
                ->where('role', 'model')
                ->first();

            if (!$existingUser) {
                \Log::warning('Model user account not found', [
                    'name' => $request->name
                ]);
                return redirect()->back()
                    ->withErrors(['name' => 'Akun model belum disiapkan. Hubungi admin.'])
                    ->withInput();
            }

            // Cek PIN
            if ($existingUser->pin !== $request->pin) {
                \Log::warning('Invalid model PIN', [
                    'name' => $request->name,
                    'provided_pin' => $request->pin,
                    'stored_pin' => $existingUser->pin
                ]);
                return redirect()->back()
                    ->withErrors(['pin' => 'PIN model tidak valid'])
                    ->withInput();
            }

            // Update akun model dengan kredensial yang didaftarkan
            $existingUser->email = $request->email;
            $existingUser->password = Hash::make($request->password);
            $existingUser->number_phone = $request->number_phone;
            $existingUser->is_verified = false;
            $existingUser->save();

            $user = $existingUser;
            \Log::info('Model user updated successfully', [
                'user_id' => $user->id_user,
                'name' => $user->name
            ]);
        } else {
            \Log::info('Processing client registration');
            // Registrasi client biasa
            try {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                    'number_phone' => $request->number_phone,
                    'is_verified' => false,
                ]);
                
                \Log::info('Client user created successfully', [
                    'user_id' => $user->id_user,
                    'name' => $user->name,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create client user', [
                    'error' => $e->getMessage(),
                    'data' => $request->all()
                ]);
                return redirect()->back()
                    ->withErrors(['general' => 'Gagal membuat akun. Silakan coba lagi.'])
                    ->withInput();
            }
        }

        // Generate OTP
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Simpan OTP ke database
        Otp::create([
            'user_id' => $user->id_user,
            'otp_code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Log untuk debugging
        \Log::info('OTP generated and saved', [
            'user_id' => $user->id_user,
            'phone' => $user->number_phone,
            'otp_code' => $otpCode,
            'role' => $user->role
        ]);

        // Kirim OTP ke WhatsApp
        try {
            $whatsappService = app(WhatsAppService::class);
            $result = $whatsappService->sendOTP($user->number_phone, $otpCode);
            
            if ($result) {
                \Log::info('WhatsApp OTP sent successfully', [
                    'user_id' => $user->id_user,
                    'phone' => $user->number_phone
                ]);
            } else {
                \Log::error('WhatsApp OTP failed to send', [
                    'user_id' => $user->id_user,
                    'phone' => $user->number_phone
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('WhatsApp OTP error', [
                'user_id' => $user->id_user,
                'phone' => $user->number_phone,
                'error' => $e->getMessage()
            ]);
        }

        // Simpan user_id di session untuk verifikasi
        Session::put('pending_user_id', $user->id_user);
        
        // Log session info
        \Log::info('Session data saved', [
            'pending_user_id' => Session::get('pending_user_id'),
            'session_id' => Session::getId()
        ]);

        // Hapus data Google dari session jika ada
        Session::forget('google_email');
        Session::forget('google_name');

        // Redirect dengan flash message
        return redirect()->route('auth.showOtpForm')
            ->with('success', __('messages.otp_sent'))
            ->with('user_id', $user->id_user);

    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $remember = $request->has('remember');

        if (auth()->attempt($request->only('email', 'password'), $remember)) {
            $user = auth()->user();

            // Cek apakah user sudah terverifikasi
            if (!$user->is_verified) {
                auth()->logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Akun Anda belum terverifikasi. Silakan cek WhatsApp untuk kode OTP.'])
                    ->withInput();
            }

            // Hapus data Google dari session jika ada
            Session::forget('google_email');
            Session::forget('google_name');

            if ($user && $user->role === 'admin') {
                return redirect()->route('admin.home')->with('success', 'Login successful');
            }
            return redirect('/')->with('success', 'Login successful');
        }

        return redirect()->back()
            ->withErrors(['email' => 'Invalid credentials'])
            ->withInput();
        }

    public function logout()
    {
        auth()->logout();
        return redirect('/')->with('success', 'Logged out successfully');
    }

    public function showOtpForm()
    {
        \Log::info('showOtpForm accessed', [
            'session_id' => Session::getId(),
            'pending_user_id' => Session::get('pending_user_id'),
            'all_session_data' => Session::all()
        ]);

        if (!Session::has('pending_user_id')) {
            \Log::warning('No pending_user_id in session, redirecting to register');
            return redirect('/register')->with('error', 'Silakan daftar terlebih dahulu');
        }

        $userId = Session::get('pending_user_id');
        $user = User::find($userId);
        
        if (!$user) {
            \Log::warning('User not found for pending_user_id', ['user_id' => $userId]);
            Session::forget('pending_user_id');
            return redirect('/register')->with('error', 'Data user tidak ditemukan. Silakan daftar ulang.');
        }

        \Log::info('OTP form shown for user', [
            'user_id' => $userId,
            'user_name' => $user->name,
            'user_email' => $user->email
        ]);

        return view('verify_otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|array|size:6',
            'otp.*' => 'required|string|size:1',
        ]);

        $otpInput = implode('', $request->otp);
        $userId = Session::get('pending_user_id');

        if (!$userId) {
            return redirect('/register')->with('error', 'Sesi registrasi tidak ditemukan');
        }

        // Cari OTP yang valid
        $otp = Otp::where('user_id', $userId)
                  ->where('otp_code', $otpInput)
                  ->where('is_used', false)
                  ->where('expires_at', '>', Carbon::now())
                  ->first();

        if (!$otp) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kadaluarsa']);
        }

        // Mark OTP as used
        $otp->update(['is_used' => true]);

        // Update user as verified
        $user = User::find($userId);
        $user->is_verified = true;
        $user->save();

        // Login user
        auth()->login($user);

        // Clear session
        Session::forget('pending_user_id');

        return redirect('/')->with('success', 'Akun berhasil diverifikasi dan Anda telah login!');
    }

    public function resendOtp()
    {
        $userId = Session::get('pending_user_id');
        
        if (!$userId) {
            return redirect('/register')->with('error', 'Sesi registrasi tidak ditemukan');
        }

        $user = User::find($userId);
        
        if (!$user) {
            return redirect('/register')->with('error', 'User tidak ditemukan');
        }

        // Generate OTP baru
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Hapus OTP lama
        Otp::where('user_id', $userId)->delete();
        
        // Simpan OTP baru
        Otp::create([
            'user_id' => $userId,
            'otp_code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Log untuk debugging
        \Log::info('OTP resend generated and saved', [
            'user_id' => $userId,
            'phone' => $user->number_phone,
            'otp_code' => $otpCode
        ]);

        // Kirim OTP baru ke WhatsApp
        try {
            $whatsappService = app(WhatsAppService::class);
            $result = $whatsappService->sendOTP($user->number_phone, $otpCode);
            
            if ($result) {
                \Log::info('WhatsApp OTP resend sent successfully', [
                    'user_id' => $userId,
                    'phone' => $user->number_phone
                ]);
            } else {
                \Log::error('WhatsApp OTP resend failed to send', [
                    'user_id' => $userId,
                    'phone' => $user->number_phone
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('WhatsApp OTP resend error', [
                'user_id' => $userId,
                'phone' => $user->number_phone,
                'error' => $e->getMessage()
            ]);
        }

        return back()->with('success', 'Kode OTP baru telah dikirim ke WhatsApp Anda');
    }
}
