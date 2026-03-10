<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractInstallment extends Model
{
    protected $table = 'contract_installments';

    const STATUS_PENDING  = 'pending';
    const STATUS_PAID     = 'paid';
    const STATUS_OVERDUE  = 'overdue';

    protected $fillable = [
        'contract_id',
        'installment_no',
        'amount',
        'due_date',
        'paid_status',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime',
        'amount'   => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
