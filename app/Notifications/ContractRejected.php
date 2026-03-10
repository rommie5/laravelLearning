<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ContractRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected Contract $contract,
        protected string $rejectedByName,
        protected ?string $reason = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail']; // Always email — high priority
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("❌ Contract Rejected: {$this->contract->contract_number}")
            ->greeting('Contract Rejected')
            ->line("Your contract **\"{$this->contract->contract_name}\"** (#{$this->contract->contract_number}) has been **rejected**.")
            ->line("**Rejected by:** {$this->rejectedByName}");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->action('View Contract', route('contracts.show', $this->contract->id))
            ->line('Please review the feedback and resubmit after making the necessary changes.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'contract',
            'subtype'         => 'contract_rejected',
            'title'           => "Contract Rejected: {$this->contract->contract_number}",
            'message'         => "Contract \"{$this->contract->contract_name}\" (#{$this->contract->contract_number}) was rejected by {$this->rejectedByName}." . ($this->reason ? " Reason: {$this->reason}" : ''),
            'icon'            => 'x-circle',
            'color'           => 'red',
            'priority'        => 'high',
            'email'           => true,
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'rejected_by'     => $this->rejectedByName,
            'reason'          => $this->reason,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ContractRejected notification failed', [
            'contract_id' => $this->contract->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
