<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $userId    = $request->user()->id;
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date',   now()->endOfMonth()->toDateString());

        $transactions = Transaction::forUser($userId)
            ->with(['account', 'category'])
            ->inDateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->get();

        $income  = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        $byCategory = $transactions
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->map(fn($group) => [
                'category' => $group->first()->category?->name,
                'type'     => $group->first()->type?->value,
                'total'    => $group->sum('amount'),
                'count'    => $group->count(),
            ])
            ->values();

        return $this->successResponse([
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'total_income' => (float) $income,
            'total_expense'=> (float) $expense,
            'net'          => (float) ($income - $expense),
            'by_category'  => $byCategory,
            'transactions' => $transactions,
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $userId    = $request->user()->id;
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date',   now()->endOfMonth()->toDateString());

        $transactions = Transaction::forUser($userId)
            ->with(['account', 'category'])
            ->inDateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->get();

        $income  = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        $pdf = Pdf::loadView('reports.transactions', [
            'transactions' => $transactions,
            'income'       => $income,
            'expense'      => $expense,
            'net'          => $income - $expense,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'user'         => $request->user(),
        ]);

        return $pdf->download("report_{$startDate}_{$endDate}.pdf");
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $userId    = $request->user()->id;
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date',   now()->endOfMonth()->toDateString());

        $transactions = Transaction::forUser($userId)
            ->with(['account', 'category'])
            ->inDateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"report_{$startDate}_{$endDate}.csv\"",
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
}
