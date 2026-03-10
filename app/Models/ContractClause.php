<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ContractClause extends Model
{
    protected $table = 'contract_clauses';

    // ── Clause type constants ──────────────────────────────────────────
    const TYPE_PERFORMANCE_SECURITY = 'performance_security';
    const TYPE_HANDOVER             = 'handover';

    const ALL_TYPES = [
        self::TYPE_PERFORMANCE_SECURITY,
        self::TYPE_HANDOVER,
    ];

    // ── Reference date type constants ──────────────────────────────────
    const REF_SIGNING = 'signing_date';  // for performance_security
    const REF_EXPIRY  = 'expiry_date';   // for handover

    const REFERENCE_DATE_MAP = [
        self::TYPE_PERFORMANCE_SECURITY => self::REF_SIGNING,
        self::TYPE_HANDOVER             => self::REF_EXPIRY,
    ];

    // ── Status constants ───────────────────────────────────────────────
    const STATUS_PENDING    = 'pending';
    const STATUS_ACTIVE     = 'active';
    const STATUS_EXPIRED    = 'expired';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_WAIVED     = 'waived';

    protected $fillable = [
        'contract_id',
        'clause_type',
        'reference_date_type',
        'period_days',
        'due_date',
        'status',
        'status_reason',
        'status_changed_by',
        'status_changed_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'due_date'          => 'date',
        'completed_at'      => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * A locked clause cannot receive further status changes.
     * Expired, terminated, and completed are all terminal (locked) states.
     */
    public function isLocked(): bool
    {
        return in_array($this->status, [
            self::STATUS_EXPIRED,
            self::STATUS_TERMINATED,
            self::STATUS_COMPLETED,
        ]);
    }

    /**
     * Compute the clause due_date from a reference date + period_days.
     *
     * @param string $clauseType        'performance_security'|'handover'
     * @param string $signingDate       Y-m-d
     * @param string $expiryDate        Y-m-d
     * @param int    $periodDays        user-defined (> 0)
     */
    public static function computeDueDate(
        string $clauseType,
        string $signingDate,
        string $expiryDate,
        int $periodDays
    ): Carbon {
        return match ($clauseType) {
            self::TYPE_PERFORMANCE_SECURITY => Carbon::parse($signingDate)->addDays($periodDays),
            self::TYPE_HANDOVER             => Carbon::parse($expiryDate)->addDays($periodDays),
            default => throw new \InvalidArgumentException("Unknown clause type: {$clauseType}"),
        };
    }

    /**
     * Return the reference_date_type string for a given clause_type.
     */
    public static function referenceDateType(string $clauseType): string
    {
        return self::REFERENCE_DATE_MAP[$clauseType]
            ?? throw new \InvalidArgumentException("Unknown clause type: {$clauseType}");
    }
}
