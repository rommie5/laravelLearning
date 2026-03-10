<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractUpdate;
use App\Models\User;
use App\Notifications\ContractCreated;
use App\Notifications\ContractStatusChanged;
use App\Notifications\ContractSubmitted;
use App\Services\AuditService;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contractService) {}

    // ──────────────────────────────────────────
    // Index — list contracts (role-scoped)
    // ──────────────────────────────────────────

    protected function getFilteredQuery(Request $request)
    {
        $user  = Auth::user();
        $query = Contract::with(['creator', 'approver'])
            ->withExists(['updates as has_pending_update' => function ($q) {
                $q->where('status', 'pending');
            }]);

        // OFFICER: own contracts only
        if ($user->hasRole('Officer')) {
            $query->where('created_by', $user->id);
        }
        // HEAD: all contracts except other people's drafts
        elseif ($user->hasRole('Head')) {
            $query->where(function ($q) use ($user) {
                $q->where('status', '!=', Contract::STATUS_DRAFT)
                  ->orWhere('created_by', $user->id);
            });
        }

        // Filters
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('contract_name', 'ilike', "%{$s}%")
                  ->orWhere('contract_number', 'ilike', "%{$s}%")
                  ->orWhere('awarded_to', 'ilike', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'pending_update') {
                $query->whereHas('updates', function ($q) {
                    $q->where('status', 'pending');
                });
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('priority'))     { $query->where('priority_level', $request->priority); }
        if ($request->filled('expiry_from'))  { $query->where('expiry_date', '>=', $request->expiry_from); }
        if ($request->filled('expiry_to'))    { $query->where('expiry_date', '<=', $request->expiry_to); }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->getFilteredQuery($request);

        return Inertia::render('Contracts/Index', [
            'contracts' => $query->latest()->paginate(15)->withQueryString(),
            'filters'   => $request->only(['search', 'status', 'priority', 'expiry_from', 'expiry_to']),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $contracts = $this->getFilteredQuery($request)->latest()->get();

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('error', 'PDF Generation Service Unavailable.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.contracts-pdf', compact('contracts'));

        AuditService::log('export_contracts_pdf', Auth::user());

        return $pdf->download('Contracts_Registry_' . now()->format('YmdHi') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $contracts = $this->getFilteredQuery($request)->latest()->get();

        $filename = "Contracts_Registry_" . now()->format('YmdHi') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Contract Number', 'Contract Name', 'Awarded To', 'Status', 'Priority', 'Expiry Date', 'Created By'];

        $callback = function() use ($contracts, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($contracts as $c) {
                fputcsv($file, [
                    $c->contract_number,
                    $c->contract_name,
                    $c->awarded_to,
                    str_replace('_', ' ', $c->status),
                    $c->priority_level,
                    $c->expiry_date ? $c->expiry_date->format('Y-m-d') : 'N/A',
                    $c->creator ? $c->creator->name : 'N/A',
                ]);
            }

            fclose($file);
        };

        AuditService::log('export_contracts_excel', Auth::user());

        return response()->stream($callback, 200, $headers);
    }


    // ──────────────────────────────────────────
    // Create form
    // ──────────────────────────────────────────

    public function create()
    {
        return Inertia::render('Contracts/Create');
    }

    public function edit(Contract $contract)
    {
        $user = Auth::user();
        if ($user->hasRole('Officer') && $contract->created_by !== $user->id) {
            abort(403);
        }
        if ($user->hasRole('Head')
            && $contract->status === Contract::STATUS_DRAFT
            && $contract->created_by !== $user->id) {
            abort(403);
        }

        $contract->load(['complianceClauses', 'installments']);

        return Inertia::render('Contracts/Edit', [
            'contract' => $contract,
            'role'     => $user->getRoleNames()->first(),
        ]);
    }
    // ──────────────────────────────────────────
    // Show
    // ──────────────────────────────────────────

    public function show(Contract $contract)
    {
        $user = Auth::user();

        if ($user->hasRole('Officer') && $contract->created_by !== $user->id) {
            abort(403);
        }
        if ($user->hasRole('Head')
            && $contract->status === Contract::STATUS_DRAFT
            && $contract->created_by !== $user->id) {
            abort(403);
        }

        $contract->load(['creator', 'approver', 'complianceClauses', 'installments', 'updates.requester']);

        return Inertia::render('Contracts/Show', ['contract' => $contract]);
    }

    // ──────────────────────────────────────────
    // Store — delegates to service (transaction, validation, collision)
    // ──────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_number'           => 'required|string|max:100|unique:contracts,contract_number',
            'contract_name'             => 'required|string|max:500',
            'awarded_to'                => 'required|string|max:500',
            'contract_site'             => 'nullable|string|max:500',
            'priority_level'            => 'required|in:low,medium,high,sensitive',
            'contract_signing_date'     => 'required|date',
            'start_date'                => 'required|date',
            'duration_value'            => 'required|integer|min:1',
            'duration_unit'             => 'required|in:weeks,months',
            'notes'                     => 'nullable|string|max:5000',
            'action'                    => 'required|in:draft,submit',

            // Optional clauses array
            'clauses'                          => 'nullable|array',
            'clauses.*.clause_type'            => 'required_with:clauses|string|in:performance_security,handover',
            'clauses.*.period_days'            => 'required_with:clauses|integer|min:1',

            // Optional installments array
            'installments'             => 'nullable|array',
            'installments.*.amount'    => 'required_with:installments|numeric|min:0.01',
            'installments.*.due_date'  => 'required_with:installments|date',
        ]);

        // CollisionValidation + transactional store happens inside the service
        $contract = $this->contractService->store($validated, $validated['action']);

        // Notify relevant parties based on action
        $actor = Auth::user();
        $heads = User::role('Head')->get();

        if ($validated['action'] === 'submit') {
            // Officer submitted on create — notify all Heads
            Notification::send($heads, new ContractSubmitted($contract, $actor->name));
        } else {
            // Draft saved — notify Heads so they're aware
            Notification::send($heads, new ContractCreated($contract, $actor->name));
        }

        return redirect()->route('contracts.index')->with(
            'success',
            $validated['action'] === 'draft'
                ? 'Contract saved as draft.'
                : 'Contract submitted for approval.'
        );
    }

    // ──────────────────────────────────────────
    // Update (direct for draft, snapshot for active)
    // ──────────────────────────────────────────

    public function update(Request $request, Contract $contract)
    {
        $user = Auth::user();
        if ($user->hasRole('Officer') && $contract->created_by !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action'                => 'required|in:draft,submit',
            'contract_number'       => 'required|string|max:100|unique:contracts,contract_number,' . $contract->id,
            'contract_name'         => 'required|string|max:500',
            'awarded_to'            => 'required|string|max:500',
            'contract_site'         => 'nullable|string|max:500',
            'priority_level'        => 'required|in:low,medium,high,sensitive',
            'contract_signing_date' => 'required|date',
            'start_date'            => 'required|date',
            'duration_value'        => 'required|integer|min:1',
            'duration_unit'         => 'required|in:weeks,months',
            'notes'                 => 'nullable|string|max:5000',

            'clauses'                       => 'nullable|array',
            'clauses.*.clause_type'         => 'required_with:clauses|string|in:performance_security,handover',
            'clauses.*.period_days'         => 'required_with:clauses|integer|min:1',

            'installments'                  => 'nullable|array',
            'installments.*.amount'         => 'required_with:installments|numeric|min:0.01',
            'installments.*.due_date'       => 'required_with:installments|date',
        ]);

        if ($validated['action'] === 'draft') {
            $this->contractService->saveDraft($contract, $validated);
            return redirect()->route('contracts.show', $contract)->with('success', 'Changes saved as draft.');
        }

        $this->contractService->update($contract, $validated);

        $message = match (true) {
            $user->hasRole('Head')   => 'Contract updated successfully.',
            $contract->status === Contract::STATUS_ACTIVE => 'Update submitted for Head approval.',
            default => 'Contract updated.',
        };

        return redirect()->route('contracts.show', $contract)->with('success', $message);
    }

    // ──────────────────────────────────────────
    // Submit draft for approval
    // ──────────────────────────────────────────

    public function submit(Contract $contract)
    {
        if ($contract->created_by !== Auth::id()) {
            abort(403);
        }

        if ($contract->status !== Contract::STATUS_DRAFT) {
            return back()->with('error', 'Only drafts can be submitted.');
        }

        DB::transaction(function () use ($contract) {

            // If user is Head → activate directly
            if (Auth::user()->hasRole('Head')) {

                $contract->update([
                    'status' => Contract::STATUS_ACTIVE
                ]);

                AuditService::log(
                    'contract_activated',
                    $contract,
                    null,
                    ['status' => Contract::STATUS_ACTIVE]
                );

            } else {

                // Officer → send for approval
                $contract->update([
                    'status' => Contract::STATUS_PENDING_APPROVAL
                ]);

                AuditService::log(
                    'contract_submitted',
                    $contract,
                    null,
                    ['status' => Contract::STATUS_PENDING_APPROVAL]
                );

                $heads = User::role('Head')->get();
                Notification::send($heads, new ContractSubmitted($contract, Auth::user()->name));
            }
        });

        return back()->with('success', 'Contract submitted successfully.');
    }

    // ──────────────────────────────────────────
    // Approve (Head only)
    // ──────────────────────────────────────────

    public function approve(Contract $contract)
    {
        if ($contract->status !== Contract::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Contract is not pending approval.');
        }
        DB::transaction(function () use ($contract) {
            $contract->update([
                'status'      => Contract::STATUS_ACTIVE,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
            AuditService::log('contract_approved', $contract, null, ['status' => Contract::STATUS_ACTIVE]);

            // ── Auto-activate all pending clauses ──────────────────────────
            // When a contract is approved, all its clauses move from pending → active.
            $contract->complianceClauses()
                ->where('status', \App\Models\ContractClause::STATUS_PENDING)
                ->each(function ($clause) {
                    $oldStatus = $clause->status;
                    $clause->update([
                        'status'            => \App\Models\ContractClause::STATUS_ACTIVE,
                        'status_changed_by' => null, // system action triggered by approval
                        'status_changed_at' => now(),
                    ]);
                    AuditService::log(
                        'clause_activated',
                        $clause,
                        ['status' => $oldStatus],
                        ['status' => \App\Models\ContractClause::STATUS_ACTIVE, 'reason' => 'Contract approved']
                    );
                });

            if ($contract->creator) {
                $contract->creator->notify(new ContractStatusChanged($contract, 'approved'));
            }
        });
        return back()->with('success', 'Contract approved and is now active.');
    }

    // ──────────────────────────────────────────
    // Reject (Head only)
    // ──────────────────────────────────────────

    public function reject(Request $request, Contract $contract)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        if ($contract->status !== Contract::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Contract is not pending approval.');
        }
        DB::transaction(function () use ($contract, $request) {
            AuditService::log('contract_rejected', $contract, null, ['reason' => $request->reason]);

            if ($contract->creator) {
                $contract->creator->notify(new ContractStatusChanged($contract, 'rejected', $request->reason));
            }
            $contract->delete();
        });
        return redirect()->route('contracts.index')->with('success', 'Contract rejected and removed.');
    }

    // ──────────────────────────────────────────
    // Approve an update (Head only)
    // ──────────────────────────────────────────

    public function approveUpdate(Contract $contract, ContractUpdate $update)
    {
        if (
            $update->contract_id !== $contract->id ||
            $update->status !== 'pending'
        ) {
            return back()->with('error', 'Invalid update request.');
        }

        DB::transaction(function () use ($contract, $update) {
            $before = $contract->load('complianceClauses', 'installments')->toArray();

            // ── Apply snapshot to contracts + clauses + installments ──
            $this->contractService->applySnapshot($contract, $update->after_snapshot);

            $update->update([
                'status'      => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            AuditService::log(
                'contract_update_approved',
                $contract->fresh(),
                $before,
                $update->after_snapshot
            );
        });

        return back()->with('success', 'Contract update approved and applied.');
    }

    // ──────────────────────────────────────────
    // Reject an update (Head only)
    // ──────────────────────────────────────────

    public function rejectUpdate(Request $request, Contract $contract, ContractUpdate $update)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        if ($update->contract_id !== $contract->id || $update->status !== 'pending') {
            return back()->with('error', 'Invalid update request.');
        }
        DB::transaction(function () use ($contract, $update, $request) {
            $update->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->reason,
                'reviewed_by'      => Auth::id(),
                'reviewed_at'      => now(),
            ]);

            AuditService::log('contract_update_rejected', $contract, null, ['reason' => $request->reason]);
        });
        return back()->with('success', 'Contract update rejected.');
    }

    // ──────────────────────────────────────────
    // Terminate (Head only)
    // ──────────────────────────────────────────

    public function terminate(Request $request, Contract $contract)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        if ($contract->status !== Contract::STATUS_ACTIVE) {
            return back()->with('error', 'Only active contracts can be terminated.');
        }
        DB::transaction(function () use ($contract, $request) {
            $contract->update(['status' => Contract::STATUS_TERMINATED, 'termination_reason' => $request->reason]);
            AuditService::log('contract_terminated', $contract, null, ['reason' => $request->reason]);
            if ($contract->creator) {
                $contract->creator->notify(new ContractStatusChanged($contract, 'terminated', $request->reason));
            }
        });
        return back()->with('success', 'Contract terminated.');
    }

    // ──────────────────────────────────────────
    // Close (Head only)
    // ──────────────────────────────────────────

    public function close(Request $request, Contract $contract)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        if ($contract->status !== Contract::STATUS_ACTIVE) {
            return back()->with('error', 'Only active contracts can be closed.');
        }
        DB::transaction(function () use ($contract, $request) {
            $contract->update(['status' => Contract::STATUS_CLOSED, 'close_reason' => $request->reason]);
            AuditService::log('contract_closed', $contract, null, ['reason' => $request->reason]);
            if ($contract->creator) {
                $contract->creator->notify(new ContractStatusChanged($contract, 'closed', $request->reason));
            }
        });
        return back()->with('success', 'Contract closed.');
    }

    // ──────────────────────────────────────────
    // Destroy (drafts only)
    // ──────────────────────────────────────────

    public function destroy(Contract $contract)
    {
        $user = Auth::user();
        if ($user->hasRole('Officer') && $contract->created_by !== $user->id) { abort(403); }
        if ($contract->status !== Contract::STATUS_DRAFT) {
            return back()->with('error', 'Only draft contracts can be deleted.');
        }
        DB::transaction(function () use ($contract) {
            AuditService::log('contract_deleted_draft', $contract, $contract->toArray(), null);
            $contract->delete();
        });
        return redirect()->route('contracts.index')->with('success', 'Draft deleted.');
    }
}
