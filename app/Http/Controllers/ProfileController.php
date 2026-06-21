<?php

namespace App\Http\Controllers;

use App\Enums\ApiResponseMessage;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->successResponse($this->formatUser($request->user()));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('profile_picture');

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $data['profile_picture'] = $request->file('profile_picture')->store('avatars', 'public');
        }

        $user->update($data);

        return $this->successResponse($this->formatUser($user), ApiResponseMessage::ProfileUpdateSuccess->value);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            return $this->errorResponse(ApiResponseMessage::InvalidCurrentPassword->value, 422);
        }

        $user->update(['password' => Hash::make($request->validated('password'))]);

        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        app(NotificationService::class)->sendPasswordChanged($user);

        return $this->successResponse(message: ApiResponseMessage::PasswordUpdateSuccess->value);
    }

    public function getNotificationPreferences(Request $request): JsonResponse
    {
        $defaults = [
            'email' => true, 'sms' => false, 'push' => true,
            'budget_alerts' => true, 'transaction_alerts' => true,
            'weekly_summary' => false, 'bill_reminders' => true,
        ];

        return $this->successResponse(
            $request->user()->notification_preferences ?? $defaults
        );
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'email'              => 'required|boolean',
            'sms'                => 'required|boolean',
            'push'               => 'required|boolean',
            'budget_alerts'      => 'required|boolean',
            'transaction_alerts' => 'required|boolean',
            'weekly_summary'     => 'required|boolean',
            'bill_reminders'     => 'required|boolean',
        ]);
        $user->update(['notification_preferences' => $validated]);

        return $this->successResponse($user->notification_preferences, 'Notification preferences updated.');
    }

    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();

        app(NotificationService::class)->sendAccountDeactivated($user);

        $user->currentAccessToken()->delete();
        $user->delete();

        return $this->successResponse(message: 'Account deactivated successfully.');
    }

    private function formatUser($user): array
    {
        return [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'mobile'               => $user->mobile,
            'email'                => $user->email,
            'currency'             => $user->currency ?? 'INR',
            'role'                 => $user->role?->value ?? 'user',
            'profile_picture'      => $user->profile_picture
                ? Storage::disk('public')->url($user->profile_picture)
                : null,
            'onboarding_completed' => (bool) $user->onboarding_completed,
        ];
    }
}
