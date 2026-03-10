<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(protected array $details) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldEmail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function shouldEmail(): bool
    {
        return in_array($this->getPriority(), ['high', 'critical']);
    }

    protected function getPriority(): string
    {
        return $this->details['priority'] ?? 'low';
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->details['subject'] ?? 'New Notification')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line($this->details['message'] ?? 'You have a new notification.')
            ->action($this->details['action_text'] ?? 'View Dashboard', $this->details['action_url'] ?? url('/'))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'system',
            'subtype'    => $this->details['subtype'] ?? 'user_action',
            'title'      => $this->details['subject'] ?? 'New Notification',
            'message'    => $this->details['message'] ?? 'You have a new notification.',
            'icon'       => $this->details['icon'] ?? 'bell',
            'color'      => $this->details['color'] ?? 'gray',
            'priority'   => $this->getPriority(),
            'email'      => $this->shouldEmail(),
            'action_url' => $this->details['action_url'] ?? url('/'),
            'link'       => $this->details['action_url'] ?? url('/'),
            'created_at' => now()->toISOString(),
        ];
    }
}
