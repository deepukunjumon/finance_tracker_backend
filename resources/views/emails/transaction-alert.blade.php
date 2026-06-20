<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:32px 16px;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
  <tr><td style="background-color:#1e293b;padding:24px 32px;">
    <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">{{ $appName }}</h1>
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="margin:0 0 16px;color:#1e293b;font-size:22px;font-weight:700;">Large Transaction Alert</h2>
    <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
      Hi {{ $userName }},
    </p>
    <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6;">
      A large transaction has been recorded on your account:
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border-radius:8px;padding:4px;margin-bottom:24px;">
      <tr>
        <td style="padding:12px 16px;color:#64748b;font-size:13px;border-bottom:1px solid #e2e8f0;">Type</td>
        <td style="padding:12px 16px;color:#1e293b;font-size:14px;font-weight:600;text-align:right;border-bottom:1px solid #e2e8f0;">{{ $type }}</td>
      </tr>
      <tr>
        <td style="padding:12px 16px;color:#64748b;font-size:13px;border-bottom:1px solid #e2e8f0;">Amount</td>
        <td style="padding:12px 16px;color:#dc2626;font-size:14px;font-weight:600;text-align:right;border-bottom:1px solid #e2e8f0;">{{ $currency }} {{ $amount }}</td>
      </tr>
      <tr>
        <td style="padding:12px 16px;color:#64748b;font-size:13px;">Account</td>
        <td style="padding:12px 16px;color:#1e293b;font-size:14px;font-weight:600;text-align:right;">{{ $accountName }}</td>
      </tr>
    </table>
    <p style="margin:0;color:#475569;font-size:14px;line-height:1.6;">
      If you didn't make this transaction, please review your account immediately.
    </p>
  </td></tr>
  <tr><td style="padding:20px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
    <p style="margin:0;color:#94a3b8;font-size:12px;text-align:center;">
      &copy; {{ $year }} {{ $appName }}. All rights reserved.
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
