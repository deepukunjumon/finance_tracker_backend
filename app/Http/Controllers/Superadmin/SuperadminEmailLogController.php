<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperadminEmailLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmailLog::query()->orderByDesc('sent_at');

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(fn ($w) => $w->where('recipient', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('template')) {
            $query->where('template', $request->query('template'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('sent_at', '>=', $request->query('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('sent_at', '<=', $request->query('end_date'));
        }

        $perPage = (int) $request->query('per_page', 20);
        $logs = $query->paginate($perPage);

        return $this->successResponse($logs);
    }

    public function show(string $id): JsonResponse
    {
        $log = EmailLog::find($id);

        if (! $log) {
            return $this->errorResponse('Email log not found.', 404);
        }

        return $this->successResponse($log);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $logs = $this->filteredQuery($request)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="email_logs.csv"',
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Recipient', 'Subject', 'Template', 'Channel', 'Status', 'Error', 'Sent At']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->recipient,
                    $log->subject,
                    $log->template ?? '',
                    $log->channel,
                    $log->status,
                    $log->error_message ?? '',
                    $log->sent_at?->toDateTimeString() ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $logs = $this->filteredQuery($request)->get();

        $pdf = Pdf::loadView('reports.email-logs', [
            'logs' => $logs,
            'user' => $request->user(),
        ]);

        return $pdf->download('email_logs.pdf');
    }

    private function filteredQuery(Request $request)
    {
        $query = EmailLog::query()->orderByDesc('sent_at');

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(fn ($w) => $w->where('recipient', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('template')) {
            $query->where('template', $request->query('template'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('sent_at', '>=', $request->query('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('sent_at', '<=', $request->query('end_date'));
        }

        return $query;
    }
}
