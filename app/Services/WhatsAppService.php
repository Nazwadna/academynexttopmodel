<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        // Ganti dengan API key dan URL provider WhatsApp Anda
        // Contoh menggunakan Fonnte
        $this->apiKey = env('WHATSAPP_API_KEY', 'YOUR_API_KEY_HERE');
        $this->baseUrl = env('WHATSAPP_BASE_URL', 'https://api.fonnte.com');
    }

    public function sendOTP($phoneNumber, $otpCode)
    {
        try {
            // Format nomor telepon (hapus + jika ada dan tambahkan 62)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "🔐 *Kode OTP Academy Next Top Model*\n\n";
            $message .= "Kode OTP Anda adalah: *{$otpCode}*\n\n";
            $message .= "Kode ini berlaku selama 5 menit.\n";
            $message .= "Jangan bagikan kode ini kepada siapapun.\n\n";
            $message .= "Terima kasih telah bergabung dengan Academy Next Top Model!";

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey
                ])->withOptions([
                    'verify' => env('HTTP_VERIFY_SSL', true) // set false di .env saat lokal bila perlu
            ])->post($this->baseUrl . '/send', [
                'target' => $formattedPhone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp OTP sent successfully', [
                    'phone' => $formattedPhone,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('WhatsApp OTP failed to send', [
                    'phone' => $formattedPhone,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp OTP error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function formatPhoneNumber($phone)
    {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // Jika belum ada 62 di depan, tambahkan
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }
} 