<?php

namespace App\Console\Commands;

use App\Models\ContractClause;
use App\Models\User;
use App\Notifications\ClauseStatusChanged;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ExpireClausesCommand extends Command
{
    protected $signature   = 'clauses:expire';
    protected $description = 'Auto-expire contract clauses whose due_date has passed and are still pending or active.';

    public function handle(): int
    {
        $heads = User::role('Head')->get();

        // Process per-row for precise audit trail and notifications
        ContractClause::query()
            ->whereIn('status', [ContractClause::STATUS_PENDING, ContractClause::STATUS_ACTIVE])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->with(['contract.creator'])
            ->chunkById(100, function ($clauses) use ($heads) {
                foreach ($clauses as $clause) {
                    DB::transaction(function () use ($clause, $heads) {
                        $oldStatus = $clause->status;

                        $clause->update([
                            'status'            => ContractClause::STATUS_EXPIRED,
                            'status_reason'     => 'Auto-expired: due date passed.',
                            'status_changed_by' => null, // system action
                            'status_changed_at' => now(),
                        ]);

                        AuditService::log(
                            'clause_auto_expired',
                            $clause,
                            ['status' => $oldStatus],
                            ['status' => ContractClause::STATUS_EXPIRED, 'reason' => 'Auto-expired: due date passed.']
                        );

                        // Recipients: creator + all Heads
                        $creator    = $clause->contract?->creator;
                        $recipients = $heads
                            ->when($creator && !$heads->contains('id', $creator->id), fn ($c) => $c->push($creator))
                            ->unique('id');

                        Notification::send(
                            $recipients,
                            new ClauseStatusChanged($clause, $oldStatus, ContractClause::STATUS_EXPIRED, 'Auto-expired: due date passed.', null)
                        );
                    });

                    $this->line("  Expired clause #{$clause->id} (contract_id: {$clause->contract_id})");
                }
            });

        $this->info('clauses:expire completed.');
        return Command::SUCCESS;
    }
}
