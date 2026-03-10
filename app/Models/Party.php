<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'type',
        'name',
        'reg_number',
        'address',
        'contact_person',
        'email',
        'phone',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
