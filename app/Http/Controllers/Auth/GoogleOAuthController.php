<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ApiResponseMessage;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(): RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect($frontendUrl . '/auth/callback?error=' . urlencode(ApiResponseMessage::GoogleOAuthFailed->value));
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name'      => $googleUser->getName(),
                'email'     => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $fragment = http_build_query([
            'token'                => $token,
            'id'                   => $user->id,
            'name'                 => $user->name,
            'email'                => $user->email,
            'currency'             => $user->currency ?? 'INR',
            'onboarding_completed' => $user->onboarding_completed ? '1' : '0',
            'role'                 => $user->role?->value ?? 'user',
        ]);

        return redirect($frontendUrl . '/auth/callback#' . $fragment);
    }
}
