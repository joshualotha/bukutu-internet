<?php

namespace App\Notifications;

use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RouterOffline extends Notification
{
    use Queueable;

    public function __construct(
        public Router $router,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔴 Router Offline Alert - ' . config('app.name'))
            ->greeting('Admin Alert')
            ->line('A router has gone offline.')
            ->line('Router: ' . $this->router->name)
            ->line('IP Address: ' . $this->router->ip_address)
            ->line('Location: ' . ($this->router->location ?? 'Not set'))
            ->line('Last Seen: ' . ($this->router->last_seen_at ? $this->router->last_seen_at->format('d M Y H:i') : 'Never'))
            ->action('View Router', url('/admin/routers/' . $this->router->id))
            ->line('Please investigate the issue as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'router_id' => $this->router->id,
            'router_name' => $this->router->name,
            'ip_address' => $this->router->ip_address,
            'location' => $this->router->location,
            'last_seen_at' => $this->router->last_seen_at?->toDateTimeString(),
        ];
    }
}
