<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line
use Illuminate\Database\Eloquent\Model;
use App\Models\Contract; // Added this line
use App\Models\User; // Added this line

class Clause extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'number',
        'title',
        'category',
        'text',
        'is_critical',
        'is_financial_impact',
        'version',
        'approved_by',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'is_financial_impact' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
