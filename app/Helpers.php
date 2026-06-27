<?php

if (! function_exists('mask_phone')) {
    /**
     * Mask a phone number for privacy: show first 4 and last 2 digits.
     * Example: "+256 771 234 567" → "+256 771 *** 67"
     */
    function mask_phone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $length = strlen($phone);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        $prefix = substr($phone, 0, 4);
        $suffix = substr($phone, -2);
        $masked = str_repeat('*', $length - 6);

        return $prefix . $masked . $suffix;
    }
}

if (! function_exists('generate_reference')) {
    /**
     * Generate a unique order reference.
     */
    function generate_reference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (\App\Models\Order::where('order_reference', $reference)->exists());

        return $reference;
    }
}

if (! function_exists('format_duration')) {
    /**
     * Format minutes into a human-readable duration string.
     */
    function format_duration(int $minutes): string
    {
        if ($minutes >= 1440) {
            $days = intdiv($minutes, 1440);
            $remaining = $minutes % 1440;
            $hours = intdiv($remaining, 60);
            $result = trans_choice('portal.days', $days, ['count' => $days]);
            if ($hours > 0) {
                $result .= ' ' . trans_choice('portal.hours', $hours, ['count' => $hours]);
            }
            return $result;
        }

        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $remaining = $minutes % 60;
            $result = trans_choice('portal.hours', $hours, ['count' => $hours]);
            if ($remaining > 0) {
                $result .= ' ' . trans_choice('portal.minutes', $remaining, ['count' => $remaining]);
            }
            return $result;
        }

        return trans_choice('portal.minutes', $minutes, ['count' => $minutes]);
    }
}
