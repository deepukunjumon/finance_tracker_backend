<?php

namespace App\Http\Controllers;

use App\Enums\ApiResponseMessage;
use App\Enums\TransactionType;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'user_id'         => $request->user()->id,
            'name'            => $request->validated('name'),
            'type'            => $request->validated('type'),
            'balance'         => $request->validated('balance'),
            'initial_balance' => $request->validated('balance'),
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

    public function adjustBalance(Request $request, string $id): JsonResponse
    {
        $userId  = $request->user()->id;
        $account = Account::forUser($userId)->find($id);

        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        $type = $request->input('type', 'adjust_by_record');

        if ($type === 'adjust_by_record') {
            $request->validate(['new_balance' => 'required|numeric']);
            $newBalance = (float) $request->input('new_balance');
            $diff = $newBalance - (float) $account->balance;

            if ($diff == 0) {
                return $this->successResponse($account, 'No change needed.');
            }

            DB::transaction(function () use ($userId, $account, $newBalance, $diff) {
                Transaction::create([
                    'user_id'    => $userId,
                    'account_id' => $account->id,
                    'type'       => TransactionType::Adjustment,
                    'amount'     => abs($diff),
                    'date'       => now()->toDateString(),
                    'time'       => now()->format('H:i'),
                    'note'       => $diff > 0
                        ? 'Balance adjustment (+' . number_format(abs($diff), 2) . ')'
                        : 'Balance adjustment (-' . number_format(abs($diff), 2) . ')',
                ]);

                $account->update(['balance' => $newBalance]);
            });

            return $this->successResponse($account->fresh(), 'Balance adjusted with transaction record.');

        } elseif ($type === 'change_initial_balance') {
            $request->validate(['new_initial_balance' => 'required|numeric']);
            $newInitial = (float) $request->input('new_initial_balance');

            $income   = Transaction::where('user_id', $userId)->where('account_id', $account->id)->where('type', 'income')->sum('amount');
            $expense  = Transaction::where('user_id', $userId)->where('account_id', $account->id)->where('type', 'expense')->sum('amount');
            $tOut     = Transaction::where('user_id', $userId)->where('account_id', $account->id)->where('type', 'transfer')->sum('amount');
            $tIn      = Transaction::where('user_id', $userId)->where('transfer_account_id', $account->id)->where('type', 'transfer')->sum('amount');
            $adjPlus  = Transaction::where('user_id', $userId)->where('account_id', $account->id)->where('type', 'adjustment')
                            ->whereRaw("note LIKE '%+%'")->sum('amount');
            $adjMinus = Transaction::where('user_id', $userId)->where('account_id', $account->id)->where('type', 'adjustment')
                            ->whereRaw("note LIKE '%-%'")->sum('amount');

            $calculatedBalance = $newInitial + $income - $expense - $tOut + $tIn + $adjPlus - $adjMinus;

            $account->update([
                'initial_balance' => $newInitial,
                'balance'         => $calculatedBalance,
            ]);

            return $this->successResponse($account->fresh(), 'Initial balance updated.');
        }

        return $this->errorResponse('Invalid adjustment type.', 422);
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

    public function setDefault(Request $request, string $id): JsonResponse
    {
        $userId  = $request->user()->id;
        $account = Account::forUser($userId)->find($id);

        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        DB::transaction(function () use ($userId, $account) {
            Account::forUser($userId)->where('is_primary', true)->update(['is_primary' => false]);
            $account->update(['is_primary' => true]);
        });

        return $this->successResponse($account->fresh(), 'Default account updated.');
    }
}
