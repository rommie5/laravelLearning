<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FirstLoginOtpNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your OTP Verification Code')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Use the following OTP code to verify your account:')
            ->line($this->code)
            ->line("This code expires in {$this->ttlMinutes} minutes.")
            ->line('If you did not request for this, contact the System Administrator.');
    }
}
