<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ContractSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected Contract $contract,
        protected string $submittedByName,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail']; // Always email — high priority
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Contract Submitted for Approval: {$this->contract->contract_number}")
            ->greeting('Action Required')
            ->line("Contract **\"{$this->contract->contract_name}\"** (#{$this->contract->contract_number}) has been submitted for your approval.")
            ->line("**Submitted by:** {$this->submittedByName}")
            ->action('Review Contract', route('contracts.show', $this->contract->id))
            ->line('Please review and approve or reject this contract at your earliest convenience.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'contract',
            'subtype'         => 'contract_submitted',
            'title'           => "Contract Submitted for Approval: {$this->contract->contract_number}",
            'message'         => "Contract \"{$this->contract->contract_name}\" (#{$this->contract->contract_number}) was submitted for approval by {$this->submittedByName}.",
            'icon'            => 'paper-airplane',
            'color'           => 'blue',
            'priority'        => 'high',
            'email'           => true,
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'submitted_by'    => $this->submittedByName,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ContractSubmitted notification failed', [
            'contract_id' => $this->contract->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
