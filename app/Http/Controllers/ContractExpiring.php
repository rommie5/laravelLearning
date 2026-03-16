<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Contract $contract,
        protected int $daysRemaining
    ) {}

    public function via($notifiable): array
    {
        // Send Database notifications only to system users
        // External emails (Contractors) only get Mail
        return $notifiable instanceof \App\Models\User
            ? ['database', 'mail']
            : ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $expiryDate = $this->contract->expiry_date
            ? \Carbon\Carbon::parse($this->contract->expiry_date)->format('d M Y')
            : 'N/A';

        $isPast = $this->daysRemaining < 0;
        $status = $isPast ? 'HAS EXPIRED' : "expires in {$this->daysRemaining} days";
        $emoji  = $isPast ? '⚠️' : '⏳';

        $subject = "{$emoji} Contract Expiry Alert: {$this->contract->contract_number}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello,')
            ->line("The contract **\"{$this->contract->contract_name}\"** (#{$this->contract->contract_number}) {$status}.")
            ->line("**Expiry Date:** {$expiryDate}")
            ->line("**Awarded To:** {$this->contract->awarded_to}");

        if ($isPast) {
            $mail->line("This contract has expired. Please ensure closure procedures are initiated immediately.");
        } elseif ($this->daysRemaining === 0) {
            $mail->line("This contract expires TODAY. Immediate attention required.");
        } else {
            $mail->line("Please prepare for handover, renewal, or closure.");
        }

        if ($notifiable instanceof \App\Models\User) {
            $mail->action('View Contract', url("/contracts/{$this->contract->id}"));
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        $isPast = $this->daysRemaining < 0;

        return [
            'type'            => 'contract',
            'subtype'         => $isPast ? 'contract_expired' : 'contract_expiring',
            'title'           => $isPast ? "Expired: {$this->contract->contract_number}" : "Expiring: {$this->contract->contract_number}",
            'message'         => "Contract \"{$this->contract->contract_name}\" " . ($isPast ? "has expired." : "expires in {$this->daysRemaining} days."),
            'icon'            => $isPast ? 'alert-triangle' : 'clock',
            'color'           => $isPast || $this->daysRemaining <= 7 ? 'red' : 'yellow',
            'priority'        => $isPast || $this->daysRemaining <= 7 ? 'high' : 'medium',
            'contract_id'     => $this->contract->id,
            'days_remaining'  => $this->daysRemaining,
            'link'            => "/contracts/{$this->contract->id}",
        ];
    }
}