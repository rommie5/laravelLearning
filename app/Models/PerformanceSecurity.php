<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;

class PerformanceSecurity extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'type',
        'amount',
        'issuing_bank',
        'start_date',
        'expiry_date',
        'file_path',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
