<?php

namespace App\Http\Controllers;

use App\Models\ContractClause;
use App\Models\ContractInstallment;
use App\Models\User;
use App\Notifications\ClauseStatusChanged;
use App\Notifications\InstallmentAlert;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ClauseController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Collect all notification recipients: contract creator + all Heads.
     */
    private function getRecipients(ContractClause $clause): \Illuminate\Support\Collection
    {
        $heads   = User::role('Head')->get();
        $creator = $clause->contract?->creator;

        return $heads
            ->when($creator && !$heads->contains('id', $creator->id), fn ($c) => $c->push($creator))
            ->unique('id');
    }

    /**
     * Apply a status change with full audit + notification pipeline.
     */
    private function applyStatusChange(
        ContractClause $clause,
        string $newStatus,
        ?string $reason,
        ?User $actor
    ): void {
        $oldStatus = $clause->status;

        $clause->update([
            'status'            => $newStatus,
            'status_reason'     => $reason,
            'status_changed_by' => $actor?->id,
            'status_changed_at' => now(),
            'completed_at'      => $newStatus === ContractClause::STATUS_COMPLETED ? now() : $clause->completed_at,
        ]);

        AuditService::log(
            'clause_status_changed',
            $clause,
            ['status' => $oldStatus],
            ['status' => $newStatus, 'reason' => $reason]
        );

        $notification = new ClauseStatusChanged($clause, $oldStatus, $newStatus, $reason, $actor);
        Notification::send($this->getRecipients($clause), $notification);
    }

    // ── Actions ────────────────────────────────────────────────────────────────

    /**
     * POST /clauses/{clause}/complete
     * Officer & Head — immediate, no reason required.
     */
    public function markCompleted(ContractClause $clause)
    {
        $user = Auth::user();

        // Officers can only touch clauses on their own contracts
        if ($user->hasRole('Officer') && $clause->contract?->created_by !== $user->id) {
            abort(403);
        }

        if ($clause->isLocked()) {
            return back()->with('error', "Clause is {$clause->status} and cannot be modified.");
        }

        DB::transaction(function () use ($clause, $user) {
            $this->applyStatusChange($clause, ContractClause::STATUS_COMPLETED, null, $user);
        });

        return back()->with('success', 'Clause marked as completed.');
    }

    /**
     * POST /clauses/{clause}/request-termination
     * Officer only — sends notification to all Heads; no status change.
     */
    public function requestTermination(Request $request, ContractClause $clause)
    {
        $user = Auth::user();

        if ($clause->contract?->created_by !== $user->id) {
            abort(403);
        }

        if ($clause->isLocked()) {
            return back()->with('error', "Clause is {$clause->status} — termination not applicable.");
        }

        $request->validate(['reason' => 'required|string|max:1000']);

        $contractNumber = $clause->contract?->contract_number ?? 'N/A';
        $clauseType     = str_replace('_', ' ', $clause->clause_type);

        // Notify all Heads
        $heads = User::role('Head')->get();
        Notification::send($heads, new ClauseStatusChanged(
            $clause,
            $clause->status,
            'termination_requested', // virtual — just for notification payload, NOT stored
            $request->reason,
            $user
        ));

        AuditService::log(
            'clause_termination_requested',
            $clause,
            ['status' => $clause->status],
            ['reason' => $request->reason]
        );

        return back()->with('success', 'Termination request sent to Head for review.');
    }

    /**
     * POST /clauses/{clause}/terminate
     * Head only — immediate, reason required.
     */
    public function terminate(Request $request, ContractClause $clause)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        if ($clause->isLocked()) {
            return back()->with('error', "Clause is already {$clause->status}.");
        }

        DB::transaction(function () use ($clause, $request) {
            $this->applyStatusChange($clause, ContractClause::STATUS_TERMINATED, $request->reason, Auth::user());
        });

        return back()->with('success', 'Clause terminated.');
    }

    /**
     * POST /clauses/{clause}/override
     * Head only — set any non-expired status, reason required.
     */
    public function overrideStatus(Request $request, ContractClause $clause)
    {
        $allowed = [
            ContractClause::STATUS_PENDING,
            ContractClause::STATUS_ACTIVE,
            ContractClause::STATUS_COMPLETED,
            ContractClause::STATUS_TERMINATED,
            ContractClause::STATUS_WAIVED,
        ];

        $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in($allowed)],
            'reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($clause, $request) {
            $this->applyStatusChange($clause, $request->status, $request->reason, Auth::user());
        });

        return back()->with('success', 'Clause status overridden to ' . $request->status . '.');
    }
}
