<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractRevision extends Model
{
    protected $fillable = [
        'contract_id',
        'old_values',
        'new_values',
        'user_id',
        'status',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
