<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\ApiResponseMessage;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuperadminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::withTrashed()
            ->withCount('accounts')
            ->latest()
            ->paginate(20);

        $users->getCollection()->transform(fn (User $u) => $this->formatUser($u));

        return $this->successResponse($users);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::withTrashed()->withCount('accounts')->find($id);

        if (! $user) {
            return $this->errorResponse(ApiResponseMessage::NotFound->value, 404);
        }

        return $this->successResponse($user);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);

        if (! $user) {
            return $this->errorResponse(ApiResponseMessage::NotFound->value, 404);
        }

        if ($user->id === $request->user()->id) {
            return $this->errorResponse(ApiResponseMessage::CannotDeleteSelf->value, 422);
        }

        if ($user->trashed()) {
            $user->restore();
            $message = 'User activated successfully.';
            $action  = 'user.activated';
        } else {
            $user->delete();
            $message = 'User deactivated successfully.';
            $action  = 'user.deactivated';
        }

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => $action,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return $this->successResponse($user->fresh(), $message);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse(ApiResponseMessage::NotFound->value, 404);
        }

        if ($user->id === $request->user()->id) {
            return $this->errorResponse(ApiResponseMessage::CannotDeleteSelf->value, 422);
        }

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'user.deleted',
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        $user->delete();

        return $this->successResponse(message: ApiResponseMessage::DeleteSuccess->value);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'role'            => $user->role?->value ?? 'user',
            'currency'        => $user->currency ?? 'INR',
            'profile_picture' => $user->profile_picture
                ? Storage::disk('public')->url($user->profile_picture)
                : null,
            'accounts_count'  => $user->accounts_count,
            'last_login_at'   => $user->last_login_at,
            'created_at'      => $user->created_at,
            'deleted_at'      => $user->deleted_at,
        ];
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);

        if (! $user) {
            return $this->errorResponse(ApiResponseMessage::NotFound->value, 404);
        }

        $user->restore();

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'user.restored',
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return $this->successResponse(message: ApiResponseMessage::UpdateSuccess->value);
    }
}
