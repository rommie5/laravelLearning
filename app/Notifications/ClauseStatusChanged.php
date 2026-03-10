<?php

namespace App\Notifications;

use App\Models\ContractClause;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ClauseStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    /**
     * @param ContractClause $clause
     * @param string         $oldStatus
     * @param string         $newStatus
     * @param string|null    $reason
     * @param User|null      $changedBy  null = system/scheduler
     */
    public function __construct(
        protected ContractClause $clause,
        protected string $oldStatus,
        protected string $newStatus,
        protected ?string $reason,
        protected ?User $changedBy,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldEmail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function shouldEmail(): bool
    {
        $priority = $this->getPriority();
        return in_array($priority, ['high', 'critical']);
    }

    protected function getPriority(): string
    {
        return match ($this->newStatus) {
            'expired', 'terminated' => 'high',
            'completed'             => 'medium',
            default                 => 'low',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contractNumber = $this->clause->contract->contract_number ?? 'N/A';
        $clauseType     = str_replace('_', ' ', $this->clause->clause_type ?? 'Clause');
        $actor          = $this->changedBy?->name ?? 'System';
        $subject        = ucfirst($this->newStatus) . ": {$clauseType} clause on Contract {$contractNumber}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Clause Status Update")
            ->line("The **{$clauseType}** clause on contract **{$contractNumber}** has changed status.")
            ->line("**From:** " . ucfirst($this->oldStatus))
            ->line("**To:** " . ucfirst($this->newStatus))
            ->line("**By:** {$actor}");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->action('View Contract', route('contracts.show', $this->clause->contract_id))
            ->line('Please review the clause status in the contract management system.');
    }

    public function toArray(object $notifiable): array
    {
        $contractNumber = $this->clause->contract->contract_number ?? 'N/A';
        $clauseType     = str_replace('_', ' ', $this->clause->clause_type ?? 'Clause');
        $actor          = $this->changedBy?->name ?? 'System (auto)';
        $priority       = $this->getPriority();

        $isCritical = in_array($this->newStatus, ['expired', 'terminated']);

        return [
            'type'            => 'clause',
            'subtype'         => 'clause_status_changed',
            'title'           => ucfirst($this->newStatus) . ': ' . $clauseType . ' Clause',
            'message'         => "Clause [{$clauseType}] on contract {$contractNumber} changed from {$this->oldStatus} to {$this->newStatus}.",
            'icon'            => $isCritical ? 'exclamation-circle' : 'document-text',
            'color'           => $isCritical ? 'red' : 'blue',
            'priority'        => $priority,
            'email'           => $this->shouldEmail(),
            'clause_id'       => $this->clause->id,
            'contract_id'     => $this->clause->contract_id,
            'contract_number' => $contractNumber,
            'clause_type'     => $clauseType,
            'old_status'      => $this->oldStatus,
            'new_status'      => $this->newStatus,
            'reason'          => $this->reason,
            'changed_by'      => $actor,
            'action_url'      => route('contracts.show', $this->clause->contract_id),
            'link'            => route('contracts.show', $this->clause->contract_id),
            'created_at'      => now()->toISOString(),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ClauseStatusChanged notification failed', [
            'clause_id'   => $this->clause->id,
            'new_status'  => $this->newStatus,
            'error'       => $exception->getMessage(),
        ]);
    }
}
