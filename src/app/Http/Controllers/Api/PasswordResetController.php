<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordResetService $passwordResetService
    ) {}

    /**
     * Send password reset OTP to user's email
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $sent = $this->passwordResetService->sendResetOtp($validated['email']);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'If the email exists, you will receive a password reset code.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'If the email exists, you will receive a password reset code.',
        ]);
    }

    /**
     * Reset password using OTP
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = $this->passwordResetService->resetPassword(
            $validated['email'],
            $validated['code'],
            $validated['password']
        );

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully. Please login with your new password.',
        ]);
    }
}
