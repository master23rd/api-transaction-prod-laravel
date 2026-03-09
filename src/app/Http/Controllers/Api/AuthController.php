<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new customer
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $result = $this->authService->register($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => $result,
        ], 201);
    }

    /**
     * Login with email and password - sends OTP for 2FA
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->tokenLogin($validated);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email. Please verify to complete login.',
            'data' => $result,
        ]);
    }

    /**
     * Verify OTP to complete login (2FA)
     */
    public function verifyLoginOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $result = $this->authService->verifyLoginOtp(
            $validated['email'],
            $validated['code']
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $result,
        ]);
    }

    /**
     * Logout the authenticated user
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get the authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request->user());

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => $user,
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remove_photo' => 'nullable|boolean',
        ]);

        $user = $this->authService->updateProfile($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->authService->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['new_password']
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get user statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->authService->getUserStats($request->user());

        return response()->json([
            'success' => true,
            'message' => 'User stats retrieved successfully',
            'data' => $stats,
        ]);
    }
}
