<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Report</title>
    <style>
        @page { margin: 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; border: 2px solid #d1d5db; padding: 24px; }
        h1   { font-size: 20px; color: #111827; margin-bottom: 4px; }
        .sub { font-size: 11px; color: #6b7280; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 8px; font-size: 11px; color: #374151; border-bottom: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; }
        .income  { color: #16a34a; }
        .expense { color: #dc2626; }
        .transfer{ color: #2563eb; }
        .summary { display: flex; gap: 24px; margin-bottom: 8px; }
        .card    { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 20px; min-width: 120px; }
        .card-label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
        .card-value { font-size: 16px; font-weight: bold; }
        .net-positive { color: #16a34a; }
        .net-negative { color: #dc2626; }
    </style>
</head>
<body>
    <h1>Transaction Report</h1>
    <div class="sub">{{ $user->name }} &mdash; {{ $start_date }} to {{ $end_date }}</div>

    <table style="width:auto; border:none; margin:0 0 16px 0;">
        <tr>
            <td style="padding:0 24px 0 0; border:none;">
                <div class="card-label">Total Income</div>
                <div class="card-value income">{{ number_format($income, 2) }}</div>
            </td>
            <td style="padding:0 24px 0 0; border:none;">
                <div class="card-label">Total Expense</div>
                <div class="card-value expense">{{ number_format($expense, 2) }}</div>
            </td>
            <td style="padding:0; border:none;">
                <div class="card-label">Net</div>
                <div class="card-value {{ $net >= 0 ? 'net-positive' : 'net-negative' }}">{{ number_format($net, 2) }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Sl No.</th>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Account</th>
                <th>Note</th>
                <th style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $i => $t)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $t->date->format('d M Y') }}</td>
                <td class="{{ $t->type?->value }}">{{ ucfirst($t->type?->value) }}</td>
                <td>{{ $t->category?->name ?? '—' }}</td>
                <td>{{ $t->account?->name ?? '—' }}</td>
                <td>{{ $t->note ?? '—' }}</td>
                <td style="text-align:right;" class="{{ $t->type?->value }}">{{ number_format($t->amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#9ca3af;">No transactions found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
