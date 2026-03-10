<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ContractExpiring extends Notification
{
    use Queueable;

    public function __construct(
        protected Contract $contract,
        protected int $days,
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
        $priority = $this->getPriority();
        return in_array($priority, ['high', 'critical']);
    }

    protected function getPriority(): string
    {
        return $this->days <= 7 ? 'high' : 'medium';
    }

    public function toMail($notifiable): MailMessage
    {
        $contractNumber = $this->contract->contract_number;
        $contractName   = $this->contract->contract_name;
        $expiryDate     = $this->contract->end_date?->format('d M Y') ?? 'N/A';

        return (new MailMessage)
            ->subject("⚠ Contract Expiring in {$this->days} Days: {$contractNumber}")
            ->greeting('Contract Expiry Alert')
            ->line("Contract **{$contractName}** (#{$contractNumber}) is expiring in **{$this->days} days**.")
            ->line("**Expiry Date:** {$expiryDate}")
            ->action('Review Contract', route('contracts.show', $this->contract->id))
            ->line('Please take action to renew or close this contract before it expires.');
    }

    public function toArray($notifiable): array
    {
        $priority = $this->getPriority();
        $isUrgent = $this->days <= 7;

        return [
            'type'            => 'contract',
            'subtype'         => 'contract_expiring',
            'title'           => "Contract Expires in {$this->days} Day" . ($this->days === 1 ? '' : 's'),
            'message'         => "Contract {$this->contract->contract_number} ({$this->contract->contract_name}) expires in {$this->days} days.",
            'icon'            => 'clock',
            'color'           => $isUrgent ? 'red' : 'yellow',
            'priority'        => $priority,
            'email'           => $this->shouldEmail(),
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'days_remaining'  => $this->days,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }
}
