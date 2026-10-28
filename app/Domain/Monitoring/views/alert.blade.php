<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #f3f3f1; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; margin: 0 auto;">
        <tr>
            <td style="background-color: #ffffff; border-radius: 8px; border-top: 4px solid #6366f1; padding: 32px;">
                <p style="margin: 0 0 16px; font-size: 12px; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; color: #6366f1;">
                    Monitoring alert
                </p>
                <h1 style="margin: 0 0 8px; font-size: 20px; line-height: 28px; color: #111827;">
                    {{ $monitorName }}
                </h1>
                <p style="margin: 0 0 24px; font-size: 14px; line-height: 22px; color: #374151;">
                    Status: <strong>{{ $status }}</strong> — level: {{ $level }}.
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-top: 1px solid #e5e7eb; font-size: 14px; line-height: 22px; color: #374151;">
                    <tr>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6; width: 40%;">Monitor type</td>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">{{ $monitorType }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">Latency</td>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">{{ $latencyMs === null ? 'not measured' : $latencyMs.' ms' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">Detail</td>
                        <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">{{ $message }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">Checked at</td>
                        <td style="padding: 10px 0;">{{ $occurredAt }}</td>
                    </tr>
                </table>
                <p style="margin: 24px 0 0; font-size: 12px; line-height: 18px; color: #6b7280;">
                    Automated message from the monitoring module. The resolution is automatic: it appears on the back-office dashboard when the monitor is healthy again.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
