<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Contract;

class ExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'export_type',
        'contract_id',
        'priority_level',
        'approval_required',
        'approval_granted',
        'exported_at',
        'ip_address',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'approval_required' => 'boolean',
        'approval_granted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
