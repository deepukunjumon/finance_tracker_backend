<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $month  = $request->query('month', now()->format('Y-m'));

        $sections = $request->filled('sections')
            ? array_map('trim', explode(',', $request->query('sections')))
            : ['accounts', 'recent_transactions', 'monthly_trend', 'income_by_category', 'expense_by_category'];

        $accounts     = Account::forUser($userId)->where('is_archived', false)->get();
        $totalBalance = $accounts->sum('balance');

        $income = Transaction::forUser($userId)
            ->where('type', 'income')
            ->inMonth($month)
            ->sum('amount');

        $expense = Transaction::forUser($userId)
            ->where('type', 'expense')
            ->inMonth($month)
            ->sum('amount');

        $data = [
            'total_balance'   => (float) $totalBalance,
            'monthly_income'  => (float) $income,
            'monthly_expense' => (float) $expense,
        ];

        if (in_array('accounts', $sections)) {
            $data['accounts'] = $accounts;
        }

        if (in_array('recent_transactions', $sections)) {
            $data['recent_transactions'] = Transaction::forUser($userId)
                ->with(['account', 'category'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        if (in_array('monthly_trend', $sections)) {
            $data['monthly_trend'] = Transaction::forUser($userId)
                ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, type, SUM(amount) as total')
                ->whereIn('type', ['income', 'expense'])
                ->where('date', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('month', 'type')
                ->orderBy('month')
                ->get();
        }

        if (in_array('expense_by_category', $sections)) {
            $data['expense_by_category'] = Transaction::forUser($userId)
                ->with('category')
                ->selectRaw('category_id, SUM(amount) as total')
                ->where('type', 'expense')
                ->inMonth($month)
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        if (in_array('income_by_category', $sections)) {
            $data['income_by_category'] = Transaction::forUser($userId)
                ->with('category')
                ->selectRaw('category_id, SUM(amount) as total')
                ->where('type', 'income')
                ->inMonth($month)
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        return $this->successResponse($data);
    }
}
