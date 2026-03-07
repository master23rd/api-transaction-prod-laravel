<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected UserService $userService,
        protected OtpService $otpService
    ) {}

    public function register(array $data): array
    {
        $user = $this->userService->registerCustomer($data);

        // Create token for immediate login after registration
        $token = $user->createToken('auth_token')->plainTextToken;

        // Send OTP for email verification
        $this->otpService->generateOtp($user);

        return [
            'user' => $this->userService->formatUserResponse($user),
            'token' => $token,
        ];
    }

    public function tokenLogin(array $credentials): ?array
    {
        $user = $this->attemptLogin($credentials);

        if (!$user) {
            return null;
        }

        // Send OTP for 2FA verification
        $this->otpService->generateOtp($user);

        return [
            'requires_otp' => true,
            'email' => $user->email,
            'expires_in_minutes' => $this->otpService->getOtpExpiryMinutes(),
        ];
    }

    public function verifyLoginOtp(string $email, string $code): ?array
    {
        $user = $this->userService->findByEmail($email);

        if (!$user) {
            return null;
        }

        $verified = $this->otpService->verifyOtp($user, $code);

        if (!$verified) {
            return null;
        }

        // Revoke previous tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $this->userService->formatUserResponse($user),
            'token' => $token,
        ];
    }

    protected function attemptLogin(array $credentials): ?User
    {
        $user = $this->userService->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    public function logout(Request $request): bool
    {
        $request->user()->currentAccessToken()->delete();
        return true;
    }

    public function getCurrentUser(User $user): array
    {
        return $this->userService->formatUserResponse($user);
    }

    public function updateProfile(User $user, array $data): array
    {
        $updatedUser = $this->userService->updateUserProfile($user, $data);
        return $this->userService->formatUserResponse($updatedUser);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }

    public function getUserStats(User $user): array
    {
        return $this->userService->getUserStats($user);
    }
}
