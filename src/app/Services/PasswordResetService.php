<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function sendResetOtp(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        $this->otpService->generateOtp($user);

        return true;
    }

    public function resetPassword(string $email, string $code, string $newPassword): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        // Verify OTP without marking email as verified
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return false;
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Delete the OTP
        $otp->delete();

        // Revoke all tokens for security
        $user->tokens()->delete();

        return true;
    }
}
