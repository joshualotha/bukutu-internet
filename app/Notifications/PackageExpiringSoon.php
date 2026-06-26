<?php

namespace App\Notifications;

use App\Models\ActiveSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageExpiringSoon extends Notification
{
    use Queueable;

    public function __construct(
        public ActiveSession $session,
        public int $minutesRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $package = $this->session->package;
        $timeStr = $this->minutesRemaining >= 60
            ? ceil($this->minutesRemaining / 60) . ' hour(s)'
            : $this->minutesRemaining . ' minutes';

        return (new MailMessage)
            ->subject('Package Expiring Soon - ' . config('app.name'))
            ->greeting('Hello ' . ($this->session->customer->full_name ?? 'Valued Customer') . '!')
            ->line('Your internet package is expiring in approximately ' . $timeStr . '.')
            ->line('Package: ' . ($package->name ?? 'N/A'))
            ->line('Expires: ' . ($this->session->expiry_time ? $this->session->expiry_time->format('d M Y H:i') : 'N/A'))
            ->action('Buy More Time', url('/packages'))
            ->line('Thank you for using ' . config('app.name') . '!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'session_id' => $this->session->id,
            'package_name' => $this->session->package->name ?? null,
            'minutes_remaining' => $this->minutesRemaining,
            'expiry_time' => $this->session->expiry_time?->toDateTimeString(),
        ];
    }
}
