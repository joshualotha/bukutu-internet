<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $package = $this->order->package;
        $session = $this->order->activeSessions()->latest()->first();

        return (new MailMessage)
            ->subject('Payment Confirmed - ' . config('app.name'))
            ->greeting('Hello ' . ($this->order->customer->full_name ?? 'Valued Customer') . '!')
            ->line('Your payment has been confirmed successfully.')
            ->line('Package: ' . ($package->name ?? 'N/A'))
            ->line('Amount: ' . number_format($this->order->amount, 0) . ' TZS')
            ->line('Order Reference: ' . $this->order->order_reference)
            ->when($session, function (MailMessage $message) use ($session) {
                return $message->line('Expires: ' . $session->expiry_time->format('d M Y H:i'));
            })
            ->action('Start Browsing', url('/'))
            ->line('Thank you for using ' . config('app.name') . '!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_reference' => $this->order->order_reference,
            'amount' => $this->order->amount,
            'package_name' => $this->order->package->name ?? null,
        ];
    }
}
