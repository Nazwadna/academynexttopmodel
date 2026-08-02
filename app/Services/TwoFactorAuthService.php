<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use App\Mail\TwoFactorCodeMail;
use Carbon\Carbon;

class TwoFactorAuthService
{
    protected $google2fa;
    protected $issuer;
    protected $qrCodeSize;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->issuer = config('app.name', 'Academy NTM');
        $this->qrCodeSize = 256;
    }

    /**
     * Generate new secret key for 2FA
     */
    public function generateSecretKey()
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code for Google Authenticator
     */
    public function generateQrCode($user, $secretKey)
    {
        $google2faUrl = $this->google2fa->getQRCodeUrl(
            $this->issuer,
            $user->email,
            $secretKey
        );

        $renderer = new ImageRenderer(
            new RendererStyle($this->qrCodeSize),
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($google2faUrl);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode($secretKey, $code)
    {
        return $this->google2fa->verifyKey($secretKey, $code);
    }

    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor($user, $secretKey, $verificationCode)
    {
        if (!$this->verifyCode($secretKey, $verificationCode)) {
            throw new \Exception('Invalid verification code');
        }

        $user->update([
            'two_factor_secret' => encrypt($secretKey),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        // Log the action
        Log::info('2FA enabled for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor($user, $password)
    {
        if (!\Hash::check($password, $user->password)) {
            throw new \Exception('Invalid password');
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);

        // Clear any backup codes
        $this->clearBackupCodes($user);

        Log::info('2FA disabled for user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes($user)
    {
        $codes = [];
        
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(str_random(8));
        }

        // Encrypt and store backup codes
        $user->update([
            'two_factor_backup_codes' => encrypt(json_encode($codes)),
            'two_factor_backup_codes_generated_at' => now(),
        ]);

        return $codes;
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode($user, $code)
    {
        if (!$user->two_factor_backup_codes) {
            return false;
        }

        $backupCodes = json_decode(decrypt($user->two_factor_backup_codes), true);
        
        if (!in_array(strtoupper($code), $backupCodes)) {
            return false;
        }

        // Remove used backup code
        $backupCodes = array_diff($backupCodes, [strtoupper($code)]);
        
        $user->update([
            'two_factor_backup_codes' => encrypt(json_encode(array_values($backupCodes))),
        ]);

        Log::info('Backup code used', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Clear backup codes
     */
    public function clearBackupCodes($user)
    {
        $user->update([
            'two_factor_backup_codes' => null,
            'two_factor_backup_codes_generated_at' => null,
        ]);
    }

    /**
     * Send email 2FA code
     */
    public function sendEmailTwoFactorCode($user)
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in cache with 10 minutes expiration
        Cache::put("2fa_email_{$user->id}", $code, 600); // 10 minutes
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($code));
            
            Log::info('2FA email code sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA email code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify email 2FA code
     */
    public function verifyEmailTwoFactorCode($user, $code)
    {
        $cachedCode = Cache::get("2fa_email_{$user->id}");
        
        if (!$cachedCode || $cachedCode !== $code) {
            return false;
        }

        // Clear the code after successful verification
        Cache::forget("2fa_email_{$user->id}");
        
        Log::info('2FA email code verified', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Send SMS 2FA code (using existing OTP system)
     */
    public function sendSmsTwoFactorCode($user)
    {
        if (!$user->phone) {
            throw new \Exception('User phone number not found');
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in cache with 10 minutes expiration
        Cache::put("2fa_sms_{$user->id}", $code, 600); // 10 minutes
        
        try {
            // Use existing OTP system
            $otpController = new \App\Http\Controllers\OtpController();
            $request = new \Illuminate\Http\Request([
                'phone' => $user->phone,
                'otp' => $code,
            ]);
            
            // Log the code for demo (in production, send actual SMS)
            Log::info("2FA SMS code for {$user->phone}: {$code}");
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA SMS code', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify SMS 2FA code
     */
    public function verifySmsTwoFactorCode($user, $code)
    {
        $cachedCode = Cache::get("2fa_sms_{$user->id}");
        
        if (!$cachedCode || $cachedCode !== $code) {
            return false;
        }

        // Clear the code after successful verification
        Cache::forget("2fa_sms_{$user->id}");
        
        Log::info('2FA SMS code verified', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Check if user should use 2FA
     */
    public function shouldUseTwoFactor($user)
    {
        return $user->two_factor_enabled;
    }

    /**
     * Get user's 2FA methods
     */
    public function getTwoFactorMethods($user)
    {
        $methods = [];
        
        if ($user->two_factor_enabled) {
            $methods[] = 'authenticator';
        }
        
        if ($user->email) {
            $methods[] = 'email';
        }
        
        if ($user->phone) {
            $methods[] = 'sms';
        }
        
        if ($user->two_factor_backup_codes) {
            $methods[] = 'backup_code';
        }
        
        return $methods;
    }

    /**
     * Validate 2FA setup
     */
    public function validateTwoFactorSetup($user, $secretKey, $verificationCode)
    {
        if (!$this->verifyCode($secretKey, $verificationCode)) {
            return [
                'valid' => false,
                'message' => 'Invalid verification code. Please check your authenticator app.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Two-factor authentication setup successful!',
        ];
    }

    /**
     * Generate recovery tokens
     */
    public function generateRecoveryTokens($user)
    {
        $tokens = [];
        
        for ($i = 0; $i < 5; $i++) {
            $tokens[] = 'RECOVER-' . strtoupper(str_random(16));
        }

        // Store tokens with 30 days expiration
        Cache::put("2fa_recovery_{$user->id}", $tokens, 30 * 24 * 60); // 30 days

        return $tokens;
    }

    /**
     * Verify recovery token
     */
    public function verifyRecoveryToken($user, $token)
    {
        $tokens = Cache::get("2fa_recovery_{$user->id}");
        
        if (!$tokens || !in_array($token, $tokens)) {
            return false;
        }

        // Remove used token
        $tokens = array_diff($tokens, [$token]);
        Cache::put("2fa_recovery_{$user->id}", array_values($tokens), 30 * 24 * 60);

        // Disable 2FA temporarily and force user to set it up again
        $user->update([
            'two_factor_enabled' => false,
        ]);

        Log::warning('Recovery token used', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Get 2FA statistics
     */
    public function getTwoFactorStatistics()
    {
        $totalUsers = \App\Models\User::count();
        $enabledUsers = \App\Models\User::where('two_factor_enabled', true)->count();
        $confirmedUsers = \App\Models\User::whereNotNull('two_factor_confirmed_at')->count();
        
        return [
            'total_users' => $totalUsers,
            'enabled_users' => $enabledUsers,
            'confirmed_users' => $confirmedUsers,
            'adoption_rate' => $totalUsers > 0 ? round(($enabledUsers / $totalUsers) * 100, 2) : 0,
            'confirmation_rate' => $enabledUsers > 0 ? round(($confirmedUsers / $enabledUsers) * 100, 2) : 0,
        ];
    }

    /**
     * Check for suspicious 2FA activity
     */
    public function checkSuspiciousActivity($user)
    {
        $recentAttempts = Cache::get("2fa_attempts_{$user->id}", []);
        $currentIp = request()->ip();
        
        // Check if there are too many failed attempts from different IPs
        $uniqueIps = array_unique(array_column($recentAttempts, 'ip'));
        
        if (count($uniqueIps) > 3 && count($recentAttempts) > 10) {
            Log::warning('Suspicious 2FA activity detected', [
                'user_id' => $user->id,
                'email' => $user->email,
                'attempts' => count($recentAttempts),
                'unique_ips' => count($uniqueIps),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Log 2FA attempt
     */
    public function logTwoFactorAttempt($user, $method, $success = false)
    {
        $attempts = Cache::get("2fa_attempts_{$user->id}", []);
        
        $attempts[] = [
            'method' => $method,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
            'success' => $success,
        ];
        
        // Keep only last 50 attempts
        $attempts = array_slice($attempts, -50);
        
        Cache::put("2fa_attempts_{$user->id}", $attempts, 24 * 60); // 24 hours
        
        // Check for suspicious activity
        $this->checkSuspiciousActivity($user);
    }
}
