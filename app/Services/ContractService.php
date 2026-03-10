<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractInstallment;
use App\Models\ContractUpdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractService
{
    // ──────────────────────────────────────────────────────────────────
    // 1. STORE — create contract + clauses + installments in one transaction
    // ──────────────────────────────────────────────────────────────────

    public function store(array $data, string $action): Contract
    {
        $status = $action === 'submit'
            ? Contract::STATUS_PENDING_APPROVAL
            : Contract::STATUS_DRAFT;

        $expiry = Contract::computeExpiry(
            $data['start_date'],
            (int) $data['duration_value'],
            $data['duration_unit']
        );

        $clauses = $this->prepareClauses(
            $data['clauses'] ?? [],
            $data['contract_signing_date'],
            $expiry->toDateString()
        );

        $this->validateClauseCollisions($clauses, $expiry);

        $installments = $data['installments'] ?? [];
        $this->validateInstallments($installments, $data['start_date'], $expiry->toDateString());

        return DB::transaction(function () use ($data, $status, $expiry, $clauses, $installments) {
            $contract = Contract::create([
                'contract_number'       => trim($data['contract_number']),
                'contract_name'         => trim($data['contract_name']),
                'awarded_to'            => trim($data['awarded_to']),
                'contract_site'         => trim($data['contract_site'] ?? ''),
                'priority_level'        => $data['priority_level'],
                'contract_signing_date' => $data['contract_signing_date'],
                'start_date'            => $data['start_date'],
                'duration_value'        => (int) $data['duration_value'],
                'duration_unit'         => $data['duration_unit'],
                'expiry_date'           => $expiry->toDateString(),
                'notes'                 => trim($data['notes'] ?? ''),
                'status'                => $status,
                'created_by'            => Auth::id(),
            ]);

            $this->syncClausesAndInstallments($contract, $clauses, $installments);

            AuditService::log(
                $status === Contract::STATUS_DRAFT ? 'contract_created_draft' : 'contract_submitted',
                $contract,
                null,
                $contract->load('complianceClauses', 'installments')->toArray()
            );

            return $contract;
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // 2. SAVE DRAFT — direct save for any role, status becomes 'draft'
    // ──────────────────────────────────────────────────────────────────

    /**
     * Immediately saves all form data to the contract (and syncs clauses/installments).
     * Sets status to 'draft' — no approval flow, no snapshot needed.
     */
    public function saveDraft(Contract $contract, array $data): void
    {
        $newExpiry = Contract::computeExpiry(
            $data['start_date'],
            (int) $data['duration_value'],
            $data['duration_unit']
        );

        $clauses = $this->prepareClauses(
            $data['clauses'] ?? [],
            $data['contract_signing_date'],
            $newExpiry->toDateString()
        );

        $installments = $data['installments'] ?? [];

        DB::transaction(function () use ($contract, $data, $newExpiry, $clauses, $installments) {
            $before = $contract->load('complianceClauses', 'installments')->toArray();

            $contract->update(array_merge(
                array_intersect_key($data, array_flip([
                    'contract_number', 'contract_name', 'awarded_to', 'contract_site',
                    'priority_level', 'contract_signing_date', 'start_date',
                    'duration_value', 'duration_unit', 'notes',
                ])),
                [
                    'expiry_date' => $newExpiry->toDateString(),
                    'status'      => Contract::STATUS_DRAFT,
                ]
            ));

            $this->syncClausesAndInstallments($contract, $clauses, $installments);

            AuditService::log('contract_saved_draft', $contract, $before, $data);
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // 3. UPDATE — snapshot workflow for active contracts
    // ──────────────────────────────────────────────────────────────────

    /**
     * Apply an update to a contract.
     * - draft/pending_approval: direct update (sync clauses + installments immediately).
     * - active, Officer: snapshot into ContractUpdate, await Head approval.
     * - active, Head: direct apply including clauses + installments.
     */
    public function update(Contract $contract, array $data): void
    {
        $newExpiry = Contract::computeExpiry(
            $data['start_date'],
            (int) $data['duration_value'],
            $data['duration_unit']
        );

        $clauses = $this->prepareClauses(
            $data['clauses'] ?? [],
            $data['contract_signing_date'],
            $newExpiry->toDateString()
        );

        $installments = $data['installments'] ?? [];

        $this->validateClauseCollisions($clauses, $newExpiry);
        $this->validateInstallments($installments, $data['start_date'], $newExpiry->toDateString());

        $user = Auth::user();

        DB::transaction(function () use ($contract, $data, $newExpiry, $clauses, $installments, $user) {

            // ── Active contract ───────────────────────────────────────
            if (in_array($contract->status, [
                Contract::STATUS_ACTIVE,
                Contract::STATUS_TERMINATED,
            ])) {
                $before = $contract->load('complianceClauses', 'installments')->toArray();

                // Head → direct apply
                if ($user->hasRole('Head')) {
                    $coreData = array_intersect_key($data, array_flip([
                        'contract_number', 'contract_name', 'awarded_to', 'contract_site',
                        'priority_level', 'contract_signing_date', 'start_date',
                        'duration_value', 'duration_unit', 'notes',
                    ]));

                    $contract->update(array_merge($coreData, [
                        'expiry_date' => $newExpiry->toDateString(),
                        'status'      => Contract::STATUS_ACTIVE,
                    ]));

                    $this->syncClausesAndInstallments($contract, $clauses, $installments);

                    AuditService::log('contract_updated_by_head', $contract, $before, $data);

                } else {
                    // Officer → snapshot and await approval
                    $after = array_merge(
                        array_intersect_key($data, array_flip([
                            'contract_number', 'contract_name', 'awarded_to', 'contract_site',
                            'priority_level', 'contract_signing_date', 'start_date',
                            'duration_value', 'duration_unit', 'notes',
                        ])),
                        [
                            'expiry_date'  => $newExpiry->toDateString(),
                            'clauses'      => $clauses,
                            'installments' => $installments,
                        ]
                    );

                    ContractUpdate::create([
                        'contract_id'     => $contract->id,
                        'before_snapshot' => $before,
                        'after_snapshot'  => $after,
                        'status'          => 'pending',
                        'requested_by'    => $user->id,
                    ]);
                    // We do not change contract status. It remains active.

                    AuditService::log('contract_update_requested', $contract, $before, $after);
                }

            }
            // ── Draft / Pending Approval → direct update ─────────────
            else {
                $before = $contract->load('complianceClauses', 'installments')->toArray();

                $coreData = array_intersect_key($data, array_flip([
                    'contract_number', 'contract_name', 'awarded_to', 'contract_site',
                    'priority_level', 'contract_signing_date', 'start_date',
                    'duration_value', 'duration_unit', 'notes',
                ]));

                $contract->update(array_merge($coreData, [
                    'expiry_date' => $newExpiry->toDateString(),
                ]));

                $this->syncClausesAndInstallments($contract, $clauses, $installments);

                AuditService::log('contract_updated', $contract, $before, $data);
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // 4. APPLY SNAPSHOT — used by approveUpdate in the controller
    // ──────────────────────────────────────────────────────────────────

    /**
     * Applies the after_snapshot from a ContractUpdate to the three tables:
     * contracts, contract_clauses, contract_installments.
     */
    public function applySnapshot(Contract $contract, array $snapshot): void
    {
        DB::transaction(function () use ($contract, $snapshot) {
            $coreFields = [
                'contract_number', 'contract_name', 'awarded_to', 'contract_site',
                'priority_level', 'contract_signing_date', 'start_date',
                'duration_value', 'duration_unit', 'expiry_date', 'notes',
            ];

            $coreData = array_intersect_key($snapshot, array_flip($coreFields));

            $contract->update(array_merge($coreData, [
                'status' => Contract::STATUS_ACTIVE,
            ]));

            $clauses      = $snapshot['clauses'] ?? [];
            $installments = $snapshot['installments'] ?? [];

            $this->syncClausesAndInstallments($contract, $clauses, $installments);
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // 5. SYNC HELPERS
    // ──────────────────────────────────────────────────────────────────

    /**
     * Delete and recreate all clauses and installments for a contract.
     * Used by store, saveDraft, update (direct paths), and applySnapshot.
     *
     * @param  array $clauses      Already-prepared (with due_date computed)
     * @param  array $installments Raw [{amount, due_date}, ...]
     */
    public function syncClausesAndInstallments(
        Contract $contract,
        array $clauses,
        array $installments
    ): void {
        // Preserve existing status/reason for clauses that are being kept
        $existingByType = $contract->complianceClauses()
            ->get()
            ->keyBy('clause_type');

        $contract->complianceClauses()->delete();
        $contract->installments()->delete();

        foreach ($clauses as $clause) {
            $existing = $existingByType->get($clause['clause_type']);

            ContractClause::create([
                'contract_id'         => $contract->id,
                'clause_type'         => $clause['clause_type'],
                'reference_date_type' => ContractClause::referenceDateType($clause['clause_type']),
                'period_days'         => (int) $clause['period_days'],
                'due_date'            => $clause['due_date'],
                // Preserve existing status if clause type already existed
                'status'              => $existing?->status ?? ContractClause::STATUS_PENDING,
                'status_reason'       => $existing?->status_reason,
                'status_changed_by'   => $existing?->status_changed_by,
                'status_changed_at'   => $existing?->status_changed_at,
                'completed_at'        => $existing?->completed_at,
                'created_by'          => $clause['created_by'] ?? Auth::id(),
            ]);
        }

        foreach ($installments as $index => $inst) {
            ContractInstallment::create([
                'contract_id'    => $contract->id,
                'installment_no' => $index + 1,
                'amount'         => $inst['amount'],
                'due_date'       => $inst['due_date'],
                'paid_status'    => $inst['paid_status'] ?? ContractInstallment::STATUS_PENDING,
                'paid_at'        => $inst['paid_at'] ?? null,
                'created_by'     => Auth::id(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // 6. CLAUSE PREPARATION
    // ──────────────────────────────────────────────────────────────────

    private function prepareClauses(array $clauseInput, string $signingDate, string $expiryDate): array
    {
        return array_map(function ($clause) use ($signingDate, $expiryDate) {
            $clause['due_date'] = ContractClause::computeDueDate(
                $clause['clause_type'],
                $signingDate,
                $expiryDate,
                (int) $clause['period_days']
            )->toDateString();
            return $clause;
        }, $clauseInput);
    }

    // ──────────────────────────────────────────────────────────────────
    // 7. COLLISION VALIDATION
    // ──────────────────────────────────────────────────────────────────

    private function validateClauseCollisions(array $clauses, Carbon $expiry): void
    {
        $indexed = collect($clauses)->keyBy('clause_type');
        $psDate  = null;

        if ($ps = $indexed->get(ContractClause::TYPE_PERFORMANCE_SECURITY)) {
            $psDate = Carbon::parse($ps['due_date']);

            if ($psDate->greaterThanOrEqualTo($expiry)) {
                throw ValidationException::withMessages([
                    'clauses.performance_security.period_days' =>
                        'Performance security due date (' . $psDate->format('d M Y') .
                        ') must be before the contract expiry date (' . $expiry->format('d M Y') . ').',
                ]);
            }
        }

        if ($ho = $indexed->get(ContractClause::TYPE_HANDOVER)) {
            $hoDate = Carbon::parse($ho['due_date']);

            if ($hoDate->lessThanOrEqualTo($expiry)) {
                throw ValidationException::withMessages([
                    'clauses.handover.period_days' =>
                        'Handover due date (' . $hoDate->format('d M Y') .
                        ') must be after the contract expiry date (' . $expiry->format('d M Y') . ').',
                ]);
            }

            if ($psDate !== null && $hoDate->lessThanOrEqualTo($psDate)) {
                throw ValidationException::withMessages([
                    'clauses.handover.period_days' =>
                        'Handover due date must be after the performance security due date (' .
                        $psDate->format('d M Y') . ').',
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // 8. INSTALLMENT VALIDATION
    // ──────────────────────────────────────────────────────────────────

    private function validateInstallments(array $installments, string $startDate, string $expiryDate): void
    {
        if (empty($installments)) {
            return;
        }

        $start  = Carbon::parse($startDate);
        $expiry = Carbon::parse($expiryDate);
        $seen   = [];
        $prev   = null;

        foreach ($installments as $i => $inst) {
            $n    = $i + 1;
            $date = Carbon::parse($inst['due_date']);

            if ($date->lt($start)) {
                throw ValidationException::withMessages([
                    "installments.{$i}.due_date" =>
                        "Installment #{$n} due date must be on or after the contract start date ({$start->format('d M Y')}).",
                ]);
            }

            if ($date->gt($expiry)) {
                throw ValidationException::withMessages([
                    "installments.{$i}.due_date" =>
                        "Installment #{$n} due date must be on or before the contract expiry date ({$expiry->format('d M Y')}).",
                ]);
            }

            if (in_array($inst['due_date'], $seen)) {
                throw ValidationException::withMessages([
                    "installments.{$i}.due_date" =>
                        "Installment #{$n} has a duplicate due date.",
                ]);
            }

            if ($prev !== null && !$date->gt($prev)) {
                throw ValidationException::withMessages([
                    "installments.{$i}.due_date" =>
                        "Installment #{$n} due date must be strictly after installment #" . ($n - 1) . ".",
                ]);
            }

            $seen[] = $inst['due_date'];
            $prev   = $date;
        }
    }
}
