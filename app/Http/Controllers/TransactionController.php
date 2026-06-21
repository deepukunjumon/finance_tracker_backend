<?php

namespace App\Http\Controllers;

use App\Enums\ApiResponseMessage;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::forUser($request->user()->id)
            ->with(['account', 'category', 'transferAccount'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        if ($request->filled('month')) {
            $query->inMonth($request->month);
        }

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($w) use ($q) {
                $w->where('note', 'like', "%{$q}%")
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                  ->orWhereHas('account', fn ($a) => $a->where('name', 'like', "%{$q}%"));
            });
        }

        $transactions = $request->filled('per_page')
            ? $query->paginate((int) $request->per_page)
            : $query->get();

        return $this->successResponse($transactions);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Transaction::forUser($request->user()->id)
            ->with(['account', 'category'])
            ->orderBy('date', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('month')) {
            $query->inMonth($request->month);
        }

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($w) use ($q) {
                $w->where('note', 'like', "%{$q}%")
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                  ->orWhereHas('account', fn ($a) => $a->where('name', 'like', "%{$q}%"));
            });
        }

        $transactions = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transactions.csv"',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sl No.', 'Date', 'Time', 'Type', 'Category', 'Account', 'Amount', 'Note']);
            foreach ($transactions as $i => $t) {
                fputcsv($handle, [
                    $i + 1,
                    $t->date?->toDateString() ?? '',
                    $t->time ?? '',
                    $t->type?->value ?? '',
                    $t->category?->name ?? '',
                    $t->account?->name ?? '',
                    $t->amount,
                    $t->note ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = $request->user()->id;

        $account = Account::forUser($userId)->find($data['account_id']);
        if (! $account) {
            return $this->errorResponse(ApiResponseMessage::AccountNotFound->value, 404);
        }

        if (isset($data['transfer_account_id'])) {
            $transferAccount = Account::forUser($userId)->find($data['transfer_account_id']);
            if (! $transferAccount) {
                return $this->errorResponse('Transfer account not found or does not belong to you.', 404);
            }
        }

        DB::transaction(function () use ($data, $userId, $account) {
            $transaction = Transaction::create(array_merge($data, ['user_id' => $userId]));

            $type = TransactionType::from($data['type']);
            if ($type === TransactionType::Income) {
                $account->increment('balance', $data['amount']);
            } elseif ($type === TransactionType::Expense) {
                $account->decrement('balance', $data['amount']);
            } elseif ($type === TransactionType::Transfer && isset($data['transfer_account_id'])) {
                $account->decrement('balance', $data['amount']);
                Account::forUser($userId)->where('id', $data['transfer_account_id'])->increment('balance', $data['amount']);
            }

            return $transaction;
        });

        $transaction = Transaction::forUser($userId)
            ->with(['account', 'category', 'transferAccount'])
            ->latest()
            ->first();

        $this->sendTransactionNotifications($request->user(), $data, $account);

        return $this->successResponse($transaction, ApiResponseMessage::CreateSuccess->value, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::forUser($request->user()->id)
            ->with(['account', 'category', 'transferAccount'])
            ->find($id);

        if (! $transaction) {
            return $this->errorResponse(ApiResponseMessage::TransactionNotFound->value, 404);
        }

        return $this->successResponse($transaction);
    }

    public function update(UpdateTransactionRequest $request, string $id): JsonResponse
    {
        $transaction = Transaction::forUser($request->user()->id)->find($id);

        if (! $transaction) {
            return $this->errorResponse(ApiResponseMessage::TransactionNotFound->value, 404);
        }

        $transaction->update($request->validated());
        $transaction->load(['account', 'category', 'transferAccount']);

        return $this->successResponse($transaction, ApiResponseMessage::UpdateSuccess->value);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::forUser($request->user()->id)->find($id);

        if (! $transaction) {
            return $this->errorResponse(ApiResponseMessage::TransactionNotFound->value, 404);
        }

        $transaction->delete();

        return $this->successResponse(message: ApiResponseMessage::DeleteSuccess->value);
    }

    private function sendTransactionNotifications($user, array $data, Account $account): void
    {
        $notifier = app(NotificationService::class);
        $amount   = (float) $data['amount'];

        $threshold = (float) config('notifications.large_transaction_threshold', 10000);
        if ($amount >= $threshold) {
            $notifier->sendTransactionAlert($user, $amount, $data['type'], $account->name);
        }

        if ($data['type'] === 'expense' && ! empty($data['category_id'])) {
            $month = date('Y-m', strtotime($data['date']));
            $year  = (int) substr($month, 0, 4);
            $mon   = (int) substr($month, 5, 2);

            $budgets = Budget::forUser($user->id)
                ->where('category_id', $data['category_id'])
                ->where('year', $year)
                ->where(fn ($q) => $q->whereNull('month')->orWhere('month', $mon))
                ->get();

            foreach ($budgets as $budget) {
                $spent = Transaction::forUser($user->id)
                    ->where('category_id', $data['category_id'])
                    ->where('type', 'expense')
                    ->inMonth($month)
                    ->sum('amount');

                if ((float) $spent > (float) $budget->amount) {
                    $categoryName = $budget->category?->name ?? 'Unknown';
                    $budget->loadMissing('category');
                    $notifier->sendBudgetExceeded($user, $budget->category?->name ?? 'Unknown', (float) $budget->amount, (float) $spent);
                }
            }
        }
    }
}
