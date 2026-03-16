<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendContractExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for contracts expiring in 30, 14, 7, 2, 0 days, or expired yesterday.';

    public function handle()
    {
        $checkPoints = [30, 14, 7, 2, 0, -1];

        foreach ($checkPoints as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $contracts = Contract::where('status', Contract::STATUS_ACTIVE)
                ->whereDate('expiry_date', $targetDate)
                ->with('creator')
                ->get();

            $this->info("Processing {$days} days remaining (Date: {$targetDate}): Found {$contracts->count()} contracts.");

            foreach ($contracts as $contract) {
                $internalRecipients = User::role('Head')->get();
                if ($contract->creator) {
                    $internalRecipients->push($contract->creator);
                }
                Notification::send($internalRecipients->unique('id'), new ContractExpiring($contract, $days));

                // 2. Notify Contractor (External via 'parties' table)
                $contractorEmail = DB::table('parties')
                    ->where('contract_id', $contract->id)
                    ->where('type', 'Contractor')
                    ->value('email');

                if ($contractorEmail) {
                    Notification::route('mail', $contractorEmail)->notify(new ContractExpiring($contract, $days));
                }
            }
        }
    }
}