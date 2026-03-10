<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status constants ───────────────────────────────────────────────
    const STATUS_DRAFT            = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_ACTIVE           = 'active';
    const STATUS_TERMINATED       = 'terminated';
    const STATUS_CLOSED           = 'closed';

    // Expired is derived dynamically — never stored
    const ALL_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_ACTIVE,
        self::STATUS_TERMINATED,
        self::STATUS_CLOSED,
    ];

    // ── Priority constants ─────────────────────────────────────────────
    const PRIORITY_LOW       = 'low';
    const PRIORITY_MEDIUM    = 'medium';
    const PRIORITY_HIGH      = 'high';
    const PRIORITY_SENSITIVE = 'sensitive';

    protected $fillable = [
        'contract_number',
        'contract_name',
        'awarded_to',
        'contract_site',
        'priority_level',
        'contract_signing_date',
        'start_date',
        'duration_value',
        'duration_unit',
        'expiry_date',
        'notes',
        'status',
        'termination_reason',
        'close_reason',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'contract_signing_date' => 'date',
        'start_date'            => 'date',
        'expiry_date'           => 'date',
        'approved_at'           => 'datetime',
    ];

    // ── Derived accessor: is the contract expired? ─────────────────────
    // The 'expired' state is never stored — computed dynamically.
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expiry_date !== null
            && $this->expiry_date->isPast();
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Compliance obligation clauses (performance security, handover, etc.)
     */
    public function complianceClauses()
    {
        return $this->hasMany(ContractClause::class);
    }

    /**
     * Dynamic payment installment schedule
     */
    public function installments()
    {
        return $this->hasMany(ContractInstallment::class)->orderBy('installment_no');
    }

    /**
     * Pending update proposals (approval-controlled edits on active contracts)
     */
    public function updates()
    {
        return $this->hasMany(ContractUpdate::class);
    }

    /**
     * Immutable revision history (snapshots)
     */
    public function revisions()
    {
        return $this->hasMany(ContractRevision::class);
    }

    // ── Static helpers ─────────────────────────────────────────────────

    /**
     * Compute the expiry date from start_date + duration.
     * This is the ONLY place this calculation lives.
     *
     * @param string $startDate  Y-m-d
     * @param int    $value
     * @param string $unit       'weeks'|'months'
     */
    public static function computeExpiry(string $startDate, int $value, string $unit): Carbon
    {
        $date = Carbon::parse($startDate);
        return match ($unit) {
            'weeks'  => $date->addWeeks($value),
            'months' => $date->addMonths($value),
            default  => throw new \InvalidArgumentException("Invalid duration unit: {$unit}"),
        };
    }
}
