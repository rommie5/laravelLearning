<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentUploaded extends Notification
{
    use Queueable;

    public function __construct(
        protected Contract $contract,
        protected string $documentName,
        protected string $uploadedByName,
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // No email — low priority informational event
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'system',
            'subtype'         => 'document_uploaded',
            'title'           => "Document Uploaded: {$this->contract->contract_number}",
            'message'         => "\"{$this->documentName}\" was uploaded to contract {$this->contract->contract_number} by {$this->uploadedByName}.",
            'icon'            => 'paper-clip',
            'color'           => 'gray',
            'priority'        => 'low',
            'email'           => false,
            'contract_id'     => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'document_name'   => $this->documentName,
            'uploaded_by'     => $this->uploadedByName,
            'action_url'      => route('contracts.show', $this->contract->id),
            'link'            => route('contracts.show', $this->contract->id),
            'created_at'      => now()->toISOString(),
        ];
    }
}
