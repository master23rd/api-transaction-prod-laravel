<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    protected int $otpLength = 6;
    protected int $expiryMinutes = 10;

    public function generateOtp(User $user): OtpCode
    {
        // Invalidate existing OTPs
        OtpCode::where('user_id', $user->id)->delete();

        // Generate new OTP
        $code = $this->generateCode();

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes($this->expiryMinutes),
        ]);

        // Send OTP via email
        $this->sendOtpEmail($user, $code);

        return $otp;
    }

    public function verifyOtp(User $user, string $code): bool
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return false;
        }

        // Mark email as verified
        $user->update(['email_verified_at' => now()]);

        // Delete the OTP
        $otp->delete();

        return true;
    }

    public function resendOtp(User $user): OtpCode
    {
        return $this->generateOtp($user);
    }

    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, pow(10, $this->otpLength) - 1), $this->otpLength, '0', STR_PAD_LEFT);
    }

    protected function sendOtpEmail(User $user, string $code): void
    {
        // In development mode with mail skip enabled, log OTP instead of sending
        if (config('app.env') === 'local' && config('mail.skip_sending', false)) {
            Log::channel('single')->info("OTP Code for {$user->email}: {$code}");
            return;
        }

        try {
            Mail::to($user->email)->send(new OtpMail(
                code: $code,
                userName: $user->name,
                expiryMinutes: $this->expiryMinutes
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email to {$user->email}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getOtpExpiryMinutes(): int
    {
        return $this->expiryMinutes;
    }
}
