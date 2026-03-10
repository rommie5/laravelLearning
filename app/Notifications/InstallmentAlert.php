<?php

namespace App\Notifications;

use App\Models\ContractInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class InstallmentAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    const TYPE_UPCOMING = 'upcoming';
    const TYPE_DUE      = 'due_today';
    const TYPE_OVERDUE  = 'overdue';
    const TYPE_PAID     = 'paid';

    public function __construct(
        protected ContractInstallment $installment,
        protected string $alertType,
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
        return in_array($this->getPriority(), ['high', 'critical']);
    }

    protected function getPriority(): string
    {
        return match ($this->alertType) {
            self::TYPE_OVERDUE  => 'high',
            self::TYPE_DUE      => 'high',
            self::TYPE_UPCOMING => 'medium',
            self::TYPE_PAID     => 'low',
            default             => 'low',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->alertType) {
            self::TYPE_PAID => 'check-circle',
            default         => 'currency-dollar',
        };
    }

    protected function getColor(): string
    {
        return match ($this->alertType) {
            self::TYPE_OVERDUE  => 'red',
            self::TYPE_DUE      => 'yellow',
            self::TYPE_UPCOMING => 'yellow',
            self::TYPE_PAID     => 'green',
            default             => 'blue',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contractNumber = $this->installment->contract->contract_number ?? 'N/A';
        $amount         = number_format($this->installment->amount, 2);
        $dueDate        = $this->installment->due_date?->format('d M Y') ?? 'N/A';

        return (new MailMessage)
            ->subject("Installment Alert: Contract {$contractNumber} — #{$this->installment->installment_no}")
            ->greeting('Installment Alert')
            ->line("Installment **#{$this->installment->installment_no}** on contract **{$contractNumber}**.")
            ->line("**Status:** " . ucfirst(str_replace('_', ' ', $this->alertType)))
            ->line("**Amount:** {$amount}")
            ->line("**Due Date:** {$dueDate}")
            ->action('View Contract', route('contracts.show', $this->installment->contract_id))
            ->line('Please take action to resolve this installment.');
    }

    public function toArray(object $notifiable): array
    {
        $contractNumber = $this->installment->contract->contract_number ?? 'N/A';
        $priority       = $this->getPriority();

        $titles = [
            self::TYPE_UPCOMING => "Installment Due in 7 Days",
            self::TYPE_DUE      => "Installment Due Today",
            self::TYPE_OVERDUE  => "Installment OVERDUE",
            self::TYPE_PAID     => "Installment Paid",
        ];

        $messages = [
            self::TYPE_UPCOMING => "Installment #{$this->installment->installment_no} on {$contractNumber} is due in 7 days.",
            self::TYPE_DUE      => "Installment #{$this->installment->installment_no} on {$contractNumber} is due today.",
            self::TYPE_OVERDUE  => "OVERDUE: Installment #{$this->installment->installment_no} on {$contractNumber} has not been paid.",
            self::TYPE_PAID     => "Installment #{$this->installment->installment_no} on {$contractNumber} has been marked as paid.",
        ];

        return [
            'type'            => 'installment',
            'subtype'         => 'installment_' . $this->alertType,
            'title'           => $titles[$this->alertType] ?? "Installment Update",
            'message'         => $messages[$this->alertType] ?? "Installment update on {$contractNumber}.",
            'icon'            => $this->getIcon(),
            'color'           => $this->getColor(),
            'priority'        => $priority,
            'email'           => $this->shouldEmail(),
            'alert_type'      => $this->alertType,
            'installment_id'  => $this->installment->id,
            'contract_id'     => $this->installment->contract_id,
            'contract_number' => $contractNumber,
            'amount'          => $this->installment->amount,
            'due_date'        => $this->installment->due_date?->format('Y-m-d'),
            'action_url'      => route('contracts.show', $this->installment->contract_id),
            'link'            => route('contracts.show', $this->installment->contract_id),
            'created_at'      => now()->toISOString(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('InstallmentAlert notification failed', [
            'installment_id' => $this->installment->id,
            'alert_type'     => $this->alertType,
            'error'          => $exception->getMessage(),
        ]);
    }
}
