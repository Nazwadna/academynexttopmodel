<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpController extends Controller
{
    /**
     * Generate and send OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^[0-9]{10,15}$/'
        ]);

        $phone = $request->phone;
        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(5);

        // Cache OTP with expiration
        Cache::put("otp_{$phone}", [
            'code' => $otp,
            'expires_at' => $expiresAt,
            'attempts' => 0
        ], 300); // 5 minutes

        // Send OTP via SMS (using Vonage/Twilio)
        $this->sendSmsOtp($phone, $otp);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'expires_in' => 300
        ]);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'otp' => 'required|digits:6'
        ]);

        $phone = $request->phone;
        $otp = $request->otp;
        $cachedOtp = Cache::get("otp_{$phone}");

        if (!$cachedOtp) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired or not found'
            ], 400);
        }

        // Check attempts
        if ($cachedOtp['attempts'] >= 3) {
            Cache::forget("otp_{$phone}");
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please request new OTP'
            ], 400);
        }

        // Check expiration
        if (Carbon::now()->gt($cachedOtp['expires_at'])) {
            Cache::forget("otp_{$phone}");
            return response()->json([
                'success' => false,
                'message' => 'OTP expired'
            ], 400);
        }

        // Verify OTP
        if ($cachedOtp['code'] !== $otp) {
            $cachedOtp['attempts']++;
            Cache::put("otp_{$phone}", $cachedOtp, 300);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
                'attempts_left' => 3 - $cachedOtp['attempts']
            ], 400);
        }

        // OTP verified successfully
        Cache::forget("otp_{$phone}");

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
    }

    /**
     * Generate 6-digit OTP
     */
    private function generateOtp()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via SMS (implementation depends on SMS provider)
     */
    private function sendSmsOtp($phone, $otp)
    {
        // For demo purposes, we'll log the OTP
        // In production, integrate with Vonage, Twilio, or local SMS gateway
        
        \Log::info("OTP for {$phone}: {$otp}");
        
        // Example with Vonage (uncomment and configure):
        /*
        $basic  = new \Vonage\Client\Credentials\Basic('api_key', 'api_secret');
        $client = new \Vonage\Client($basic);
        
        $message = $client->sms()->send(new \Vonage\SMS\Message\SMS(
            $phone,
            'Academy NTM',
            "Your OTP code is: {$otp}. Valid for 5 minutes."
        ));
        
        \Log::info('SMS sent: ' . $message->getStatus());
        */
        
        // For now, return success (in production, implement actual SMS)
        return true;
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^[0-9]{10,15}$/'
        ]);

        $phone = $request->phone;
        
        // Check if there's an existing OTP that hasn't expired
        $existingOtp = Cache::get("otp_{$phone}");
        
        if ($existingOtp && Carbon::now()->lt($existingOtp['expires_at'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting new OTP'
            ], 429);
        }

        return $this->sendOtp($request);
    }
}
