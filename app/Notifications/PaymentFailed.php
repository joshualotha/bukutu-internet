<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed - ' . config('app.name'))
            ->greeting('Hello ' . ($this->order->customer->full_name ?? 'Valued Customer') . '!')
            ->line('Unfortunately, your payment could not be processed.')
            ->line('Order Reference: ' . $this->order->order_reference)
            ->line('Amount: ' . number_format($this->order->amount, 0) . ' UGX')
            ->when($this->reason, function (MailMessage $message) {
                return $message->line('Reason: ' . $this->reason);
            })
            ->line('Please try again or contact support if the issue persists.')
            ->action('Try Again', url('/packages'))
            ->line('Thank you for your patience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_reference' => $this->order->order_reference,
            'amount' => $this->order->amount,
            'reason' => $this->reason,
        ];
    }
}
