<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    private function configureSocialiteDriver(): void
    {
        Config::set('services.sso', [
            'client_id'     => AppSetting::get('sso_client_id',     ''),
            'client_secret' => AppSetting::get('sso_client_secret', ''),
            'redirect'      => AppSetting::get('sso_redirect_url',  url('/api/auth/sso/callback')),
            'base_url'      => AppSetting::get('sso_authorization_url', ''),
            'token_url'     => AppSetting::get('sso_token_url',     ''),
            'userinfo_url'  => AppSetting::get('sso_userinfo_url',  ''),
        ]);
    }

    public function redirect(): RedirectResponse
    {
        if (! AppSetting::get('sso_enabled', false)) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=SSO+not+enabled');
        }

        $this->configureSocialiteDriver();

        return Socialite::driver('generic-oauth2')->stateless()->redirect();
    }

    public function callback(): RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        try {
            $this->configureSocialiteDriver();
            $ssoUser = Socialite::driver('generic-oauth2')->stateless()->user();
        } catch (\Exception $e) {
            return redirect($frontendUrl . '/auth/sso/callback?error=' . urlencode('SSO authentication failed.'));
        }

        $user = User::where('email', $ssoUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name'  => $ssoUser->getName() ?? $ssoUser->getEmail(),
                'email' => $ssoUser->getEmail(),
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

        return redirect($frontendUrl . '/auth/sso/callback#' . $fragment);
    }
}
