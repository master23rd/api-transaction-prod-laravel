<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Send OTP to user's email
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        $otp = $this->otpService->generateOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'expires_in_minutes' => $this->otpService->getOtpExpiryMinutes(),
            ],
        ]);
    }

    /**
     * Verify OTP code
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        $verified = $this->otpService->verifyOtp($user, $validated['code']);

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
        ]);
    }

    /**
     * Resend OTP to user's email
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        $otp = $this->otpService->resendOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully',
            'data' => [
                'expires_in_minutes' => $this->otpService->getOtpExpiryMinutes(),
            ],
        ]);
    }
}
