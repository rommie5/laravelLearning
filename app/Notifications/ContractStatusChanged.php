<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected $contract,
        protected string $eventType, // e.g. 'submitted', 'approved', 'rejected', 'terminated', 'closed'
        protected ?string $reason = null,
        protected ?string $actorName = null,
    ) {}

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
        return match ($this->eventType) {
            'approved', 'rejected' => 'high',
            'submitted'            => 'medium',
            'terminated'           => 'high',
            default                => 'low',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->eventType) {
            'approved'   => 'check-circle',
            'rejected'   => 'x-circle',
            'submitted'  => 'paper-airplane',
            'terminated' => 'exclamation-circle',
            'closed'     => 'lock-closed',
            default      => 'refresh',
        };
    }

    protected function getColor(): string
    {
        return match ($this->eventType) {
            'approved'   => 'green',
            'rejected'   => 'red',
            'terminated' => 'red',
            'submitted'  => 'blue',
            'closed'     => 'gray',
            default      => 'blue',
        };
    }

    protected function getTitle(): string
    {
        $num = $this->contract->contract_number ?? '';
        return match ($this->eventType) {
            'approved'   => "Contract Approved: {$num}",
            'rejected'   => "Contract Rejected: {$num}",
            'submitted'  => "Contract Submitted for Approval: {$num}",
            'terminated' => "Contract Terminated: {$num}",
            'closed'     => "Contract Closed: {$num}",
            default      => "Contract Updated: {$num}",
        };
    }

    protected function getMessage(): string
    {
        $name  = $this->contract->contract_name   ?? 'Unknown';
        $num   = $this->contract->contract_number ?? 'N/A';
        $actor = $this->actorName ?? 'System';

        return match ($this->eventType) {
            'approved'   => "Contract \"{$name}\" ({$num}) was approved by {$actor}.",
            'rejected'   => "Contract \"{$name}\" ({$num}) was rejected." . ($this->reason ? " Reason: {$this->reason}" : ''),
            'submitted'  => "Contract \"{$name}\" ({$num}) was submitted for approval by {$actor}.",
            'terminated' => "Contract \"{$name}\" ({$num}) was terminated." . ($this->reason ? " Reason: {$this->reason}" : ''),
            'closed'     => "Contract \"{$name}\" ({$num}) has been closed.",
            default      => "Contract \"{$name}\" ({$num}) status changed to {$this->eventType}.",
        };
    }

    public function toMail($notifiable): MailMessage
    {
        $actionUrl = route('contracts.show', $this->contract->id);

        $mail = (new MailMessage)
            ->subject($this->getTitle())
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line($this->getMessage());

        if ($this->reason) {
            $mail->line('**Reason:** ' . $this->reason);
        }

        return $mail
            ->action('View Contract', $actionUrl)
            ->line('This is an automated notification from ' . config('app.name') . '.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'contract',
            'subtype'         => 'contract_' . $this->eventType,
            'title'           => $this->getTitle(),
            'message'         => $this->getMessage(),
            'icon'            => $this->getIcon(),
            'color'           => $this->getColor(),
            'priority'        => $this->getPriority(),
            'email'           => $this->shouldEmail(),
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'event_type'      => $this->eventType,
            'reason'          => $this->reason,
            'actor'           => $this->actorName,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }
}