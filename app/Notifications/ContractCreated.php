<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContractCreated extends Notification
{
    use Queueable;

    public function __construct(
        protected Contract $contract,
        protected string $createdByName,
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // No email — low priority event
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'contract',
            'subtype'         => 'contract_created',
            'title'           => "New Contract Created: {$this->contract->contract_number}",
            'message'         => "Contract \"{$this->contract->contract_name}\" (#{$this->contract->contract_number}) was created by {$this->createdByName}.",
            'icon'            => 'document-add',
            'color'           => 'blue',
            'priority'        => 'low',
            'email'           => false,
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name'   => $this->contract->contract_name,
            'created_by'      => $this->createdByName,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }
}
