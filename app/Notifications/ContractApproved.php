<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ContractApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected Contract $contract,
        protected string $approvedByName,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail']; // Always email — high priority
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Contract Approved: {$this->contract->contract_number}")
            ->greeting('Good News!')
            ->line("Your contract **\"{$this->contract->contract_name}\"** (#{$this->contract->contract_number}) has been **approved**.")
            ->line("**Approved by:** {$this->approvedByName}")
            ->action('View Contract', route('contracts.show', $this->contract->id))
            ->line('The contract is now active. You can view the full details using the link above.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'contract',
            'subtype'         => 'contract_approved',
            'title'           => "Contract Approved: {$this->contract->contract_number}",
            'message'         => "Contract \"{$this->contract->contract_name}\" (#{$this->contract->contract_number}) was approved by {$this->approvedByName}.",
            'icon'            => 'check-circle',
            'color'           => 'green',
            'priority'        => 'high',
            'email'           => true,
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'approved_by'     => $this->approvedByName,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ContractApproved notification failed', [
            'contract_id' => $this->contract->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
