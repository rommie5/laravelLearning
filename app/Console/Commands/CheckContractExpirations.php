<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiring;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckContractExpirations extends Command
{
    protected $signature = 'contracts:check-expirations';
    protected $description = 'Scan the registry for contracts nearing their termination date and issue formal alerts.';

    public function handle()
    {
        $intervals = [30, 15, 5];
        
        foreach ($intervals as $days) {
            $targetDate = Carbon::now()->addDays($days)->toDateString();
            
            $contracts = Contract::where('status', 'active')
                ->whereDate('end_date', $targetDate)
                ->get();

            foreach ($contracts as $contract) {
                // Notify Creator
                $creator = User::find($contract->created_by);
                if ($creator) {
                    $creator->notify(new ContractExpiring($contract, $days));
                }

                // Notify Heads (Institutional Oversight)
                $heads = User::role('Head')->get();
                Notification::send($heads, new ContractExpiring($contract, $days));
                
                $this->info("Issued alerts for {$contract->reference_number} ({$days} days remaining).");
            }
        }

        return Command::SUCCESS;
    }
}
