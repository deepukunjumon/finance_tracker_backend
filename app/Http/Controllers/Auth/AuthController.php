<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ApiResponseMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name'     => $request->validated('name'),
                'email'    => $request->validated('email'),
                'mobile'   => $request->validated('mobile'),
                'password' => $request->validated('password'),
            ]);
    
            app(NotificationService::class)->sendWelcome($user);

            return $this->successResponse(
                $this->formatUser($user, $user->createToken('auth_token')->plainTextToken),
                ApiResponseMessage::RegisterSuccess->value,
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(ApiResponseMessage::RegisterFailed->value, 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            Log::warning('Failed login attempt.', [
                'email' => $request->validated('email'),
                'ip'    => $request->ip(),
            ]);
            return $this->errorResponse(ApiResponseMessage::InvalidCredentials->value, 401);
        }

        $user->update(['last_login_at' => now()]);

        return $this->successResponse(
            $this->formatUser($user, $user->createToken('auth_token')->plainTextToken),
            ApiResponseMessage::LoginSuccess->value
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(message: ApiResponseMessage::LogoutSuccess->value);
    }

    private function formatUser(User $user, string $token): array
    {
        return [
            'user'  => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'mobile'               => $user->mobile,
                'email'                => $user->email,
                'currency'             => $user->currency ?? 'INR',
                'onboarding_completed' => (bool) $user->onboarding_completed,
                'role'                 => $user->role?->value ?? 'user',
                'profile_picture'      => $user->profile_picture,
            ],
            'token' => $token,
        ];
    }
}
