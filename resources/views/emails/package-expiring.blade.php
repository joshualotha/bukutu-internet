<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="background: #f59e0b; padding: 24px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">{{ config('app.name') }}</h1>
            <p style="color: #fde68a; margin: 8px 0 0;">Package Expiring Soon</p>
        </div>

        <div style="padding: 32px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: #fef3c7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                    <span style="font-size: 32px; color: #d97706;">⏰</span>
                </div>
            </div>

            <h2 style="color: #111827; margin: 0 0 16px; text-align: center;">Your Package is Expiring</h2>
            <p style="color: #6b7280; text-align: center; margin: 0 0 24px;">
                @if($minutesRemaining >= 60)
                    Your internet package will expire in approximately {{ ceil($minutesRemaining / 60) }} hour(s).
                @else
                    Your internet package will expire in approximately {{ $minutesRemaining }} minutes.
                @endif
            </p>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Package</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600; text-align: right;">{{ $session->package->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Expires</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600; text-align: right;">{{ $session->expiry_time ? $session->expiry_time->format('d M Y H:i') : 'N/A' }}</td>
                </tr>
            </table>

            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ url('/packages') }}" style="display: inline-block; padding: 12px 32px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">Buy More Time</a>
            </div>
        </div>

        <div style="background: #f9fafb; padding: 16px 32px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">{{ config('app.name') }} — {{ __('portal.tagline') }}</p>
        </div>
    </div>
</body>
</html>
