<?php

namespace App\Console\Commands;

use App\Models\ContractInstallment;
use App\Models\User;
use App\Notifications\InstallmentAlert;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MarkOverdueInstallmentsCommand extends Command
{
    protected $signature   = 'installments:mark-overdue';
    protected $description = 'Mark unpaid installments whose due_date has passed as overdue, and send alerts.';

    public function handle(): int
    {
        $heads = User::role('Head')->get();

        ContractInstallment::query()
            ->where('paid_status', ContractInstallment::STATUS_PENDING)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->with(['contract.creator'])
            ->chunkById(100, function ($installments) use ($heads) {
                foreach ($installments as $installment) {
                    DB::transaction(function () use ($installment, $heads) {
                        $installment->update([
                            'paid_status' => ContractInstallment::STATUS_OVERDUE,
                        ]);

                        AuditService::log(
                            'installment_auto_overdue',
                            $installment,
                            ['paid_status' => ContractInstallment::STATUS_PENDING],
                            ['paid_status' => ContractInstallment::STATUS_OVERDUE]
                        );

                        // Recipients: creator + all Heads
                        $creator    = $installment->contract?->creator;
                        $recipients = $heads
                            ->when($creator && !$heads->contains('id', $creator->id), fn ($c) => $c->push($creator))
                            ->unique('id');

                        Notification::send(
                            $recipients,
                            new InstallmentAlert($installment, InstallmentAlert::TYPE_OVERDUE)
                        );
                    });

                    $this->line("  Overdue installment #{$installment->id} (contract_id: {$installment->contract_id})");
                }
            });

        $this->info('installments:mark-overdue completed.');
        return Command::SUCCESS;
    }
}
