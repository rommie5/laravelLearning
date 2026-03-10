<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractUpdate extends Model
{
    protected $table = 'contract_updates';

    protected $fillable = [
        'contract_id',
        'before_snapshot',
        'after_snapshot',
        'status',
        'rejection_reason',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'after_snapshot'  => 'array',
        'reviewed_at'     => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
