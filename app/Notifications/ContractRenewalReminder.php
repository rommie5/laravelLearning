<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ContractRenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected Contract $contract,
        protected int $daysRemaining = 30,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail']; // Always email — renewal CTA is important
    }

    public function toMail($notifiable): MailMessage
    {
        $expiryDate = $this->contract->end_date?->format('d M Y') ?? 'N/A';

        return (new MailMessage)
            ->subject("🔄 Renewal Reminder: Contract {$this->contract->contract_number} Expires in {$this->daysRemaining} Days")
            ->greeting('Contract Renewal Reminder')
            ->line("Contract **\"{$this->contract->contract_name}\"** (#{$this->contract->contract_number}) is expiring in **{$this->daysRemaining} days**.")
            ->line("**Expiry Date:** {$expiryDate}")
            ->line("Please initiate the renewal process to ensure continuity of service.")
            ->action('Review & Renew Contract', route('contracts.show', $this->contract->id))
            ->line('If you do not wish to renew, please mark the contract for closure before the expiry date.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'contract',
            'subtype'         => 'contract_renewal_reminder',
            'title'           => "Renewal Reminder: {$this->contract->contract_number} Expires in {$this->daysRemaining} Days",
            'message'         => "Contract \"{$this->contract->contract_name}\" (#{$this->contract->contract_number}) expires in {$this->daysRemaining} days. Please initiate renewal.",
            'icon'            => 'refresh',
            'color'           => 'yellow',
            'priority'        => 'high',
            'email'           => true,
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'days_remaining'  => $this->daysRemaining,
            'expiry_date'     => $this->contract->end_date?->format('Y-m-d'),
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ContractRenewalReminder notification failed', [
            'contract_id'    => $this->contract->id,
            'days_remaining' => $this->daysRemaining,
            'error'          => $exception->getMessage(),
        ]);
    }
}
