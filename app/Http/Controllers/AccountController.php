<?php

namespace App\Http\Controllers;

use App\Enums\ApiResponseMessage;
use App\Http\Requests\AdjustBalanceRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = Account::forUser($request->user()->id)
            ->orderBy('created_at')
            ->get();

        return $this->successResponse($accounts);
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create([
            'user_id' => $request->user()->id,
            'name'    => $request->validated('name'),
            'type'    => $request->validated('type'),
            'balance' => $request->validated('balance'),
        ]);

        return $this->successResponse($account, ApiResponseMessage::CreateSuccess->value, 201);
    }

    public function update(UpdateAccountRequest $request, string $id): JsonResponse
    {
        $account = Account::forUser($request->user()->id)->find($id);

        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        $account->update($request->validated());

        return $this->successResponse($account, ApiResponseMessage::UpdateSuccess->value);
    }

    public function adjustBalance(AdjustBalanceRequest $request, string $id): JsonResponse
    {
        $account = Account::forUser($request->user()->id)->find($id);

        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        $newBalance = (float) $request->validated('new_balance');

        $account->update(['balance' => $newBalance]);

        return $this->successResponse($account, ApiResponseMessage::UpdateSuccess->value);
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $account = Account::forUser($request->user()->id)->find($id);

        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        $account->update(['is_archived' => ! $account->is_archived]);

        return $this->successResponse($account, ApiResponseMessage::AccountArchived->value);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $account = Account::forUser($request->user()->id)->find($id);

        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        $account->delete();

        return $this->successResponse(message: ApiResponseMessage::DeleteSuccess->value);
    }
}
