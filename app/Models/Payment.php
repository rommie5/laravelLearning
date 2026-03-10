<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'description',
        'amount',
        'due_date',
        'status',
        'payment_date',
        'payment_reference',
        'proof_file_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
