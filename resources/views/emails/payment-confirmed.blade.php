<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: #2563eb; padding: 24px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">{{ config('app.name') }}</h1>
            <p style="color: #bfdbfe; margin: 8px 0 0;">Payment Confirmed</p>
        </div>

        <!-- Content -->
        <div style="padding: 32px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: #d1fae5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                    <span style="font-size: 32px; color: #059669;">✓</span>
                </div>
            </div>

            <h2 style="color: #111827; margin: 0 0 16px; text-align: center;">Payment Successful!</h2>
            <p style="color: #6b7280; text-align: center; margin: 0 0 24px;">Your internet access is now active.</p>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Package</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600; text-align: right;">{{ $order->package->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Amount</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600; text-align: right;">{{ number_format($order->amount, 0) }} UGX</td>
                </tr>
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Reference</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600; text-align: right; font-family: monospace;">{{ $order->order_reference }}</td>
                </tr>
                @if(isset($session) && $session)
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Expires</td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600; text-align: right;">{{ $session->expiry_time->format('d M Y H:i') }}</td>
                </tr>
                @endif
            </table>

            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ url('/') }}" style="display: inline-block; padding: 12px 32px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">Start Browsing</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #f9fafb; padding: 16px 32px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">{{ config('app.name') }} — {{ __('portal.tagline') }}</p>
        </div>
    </div>
</body>
</html>
