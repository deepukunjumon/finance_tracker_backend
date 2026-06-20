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
    <h2 style="margin:0 0 16px;color:#1e293b;font-size:22px;font-weight:700;">Password Changed</h2>
    <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
      Hi {{ $userName }},
    </p>
    <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
      Your password has been changed successfully. If you made this change, no further action is required.
    </p>
    <div style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin-bottom:24px;">
      <p style="margin:0;color:#dc2626;font-size:14px;font-weight:600;">
        Didn't change your password?
      </p>
      <p style="margin:8px 0 0;color:#991b1b;font-size:13px;line-height:1.5;">
        If you didn't make this change, your account may be compromised. Please contact support immediately.
      </p>
    </div>
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
