<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Email Logs</title>
<style>
  @page { margin: 40px; }
  body { font-family: sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 24px; border: 2px solid #d1d5db; }
  h1 { font-size: 18px; margin-bottom: 4px; }
  .meta { color: #64748b; font-size: 11px; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th { background: #f1f5f9; text-align: left; padding: 8px 6px; font-size: 11px; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
  td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
  .sent { color: #16a34a; font-weight: 600; }
  .failed { color: #dc2626; font-weight: 600; }
  .footer { margin-top: 24px; text-align: center; color: #94a3b8; font-size: 10px; }
</style>
</head>
<body>
  <h1>{{ config('app.name') }} — Email Logs</h1>
  <p class="meta">Exported on {{ now()->format('d M Y, h:i A') }} by {{ $user->name }}</p>

  <table>
    <thead>
      <tr>
        <th>Sl No.</th>
        <th>Recipient</th>
        <th>Subject</th>
        <th>Template</th>
        <th>Channel</th>
        <th>Status</th>
        <th>Sent At</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($logs as $i => $log)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $log->recipient }}</td>
        <td>{{ $log->subject }}</td>
        <td>{{ $log->template ?? '—' }}</td>
        <td>{{ ucfirst($log->channel) }}</td>
        <td class="{{ $log->status }}">{{ ucfirst($log->status) }}</td>
        <td>{{ $log->sent_at?->format('d M Y, h:i A') ?? '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;">No logs found.</td></tr>
      @endforelse
    </tbody>
  </table>

  <p class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
</body>
</html>
