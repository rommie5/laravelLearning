<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractRevision;

class VersionControlService
{
    /**
     * Create a permanent snapshot of the contract's current state.
     */
    public static function createSnapshot(Contract $contract): array
    {
        return $contract->load([
            'parties',
            'clauses',
            'payments',
            'performanceSecurities',
            'documents'
        ])->toArray();
    }

    /**
     * Compare two snapshots and return the diff.
     */
    public static function diff(array $old, array $new): array
    {
        // Simple diff for now, can be expanded for legal-grade character diffing
        return [
            'old' => $old,
            'new' => $new,
        ];
    }
}
