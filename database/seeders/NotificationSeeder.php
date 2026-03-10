<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractInstallment;
use App\Models\User;
use App\Notifications\ClauseStatusChanged;
use App\Notifications\ContractApproved;
use App\Notifications\ContractCreated;
use App\Notifications\ContractExpiring;
use App\Notifications\ContractRejected;
use App\Notifications\ContractRenewalReminder;
use App\Notifications\ContractStatusChanged;
use App\Notifications\ContractSubmitted;
use App\Notifications\DocumentUploaded;
use App\Notifications\InstallmentAlert;
use App\Notifications\UserActionNotification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        // Target the first available user for demo notifications
        $user = User::first();

        if (!$user) {
            $this->command->warn('No users found. Run DatabaseSeeder first.');
            return;
        }

        // Get or create dummy models for testing
        $contract = Contract::first();

        if (!$contract) {
            $this->command->warn('No contracts found. Run DatabaseSeeder first.');
            return;
        }

        $clause      = ContractClause::where('contract_id', $contract->id)->first();
        $installment = ContractInstallment::where('contract_id', $contract->id)->first();

        $this->command->info("Sending demo notifications to: {$user->name}");

        // 1. ContractCreated — low priority, blue
        $user->notify(new ContractCreated($contract, 'System Admin'));

        // 2. ContractSubmitted — high priority, blue + email
        $user->notify(new ContractSubmitted($contract, 'Contracts Officer'));

        // 3. ContractApproved — high priority, green + email
        $user->notify(new ContractApproved($contract, 'Department Head'));

        // 4. ContractRejected — high priority, red + email
        $user->notify(new ContractRejected($contract, 'Department Head', 'Missing vendor documentation.'));

        // 5. ContractStatusChanged — medium, blue (submitted)
        $user->notify(new ContractStatusChanged($contract, 'submitted', null, 'Contracts Officer'));

        // 6. ContractExpiring — ≤7 days = red + email
        $user->notify(new ContractExpiring($contract, 5));

        // 7. ContractExpiring — >7 days = yellow, no email
        $user->notify(new ContractExpiring($contract, 15));

        // 8. ContractRenewalReminder — 30 days, yellow + email
        $user->notify(new ContractRenewalReminder($contract, 30));

        // 9. ClauseStatusChanged — expired (red + email)
        if ($clause) {
            $user->notify(new ClauseStatusChanged($clause, 'pending', 'expired', 'Deadline passed', null));
        }

        // 10. InstallmentAlert overdue — red + email
        if ($installment) {
            $user->notify(new InstallmentAlert($installment, InstallmentAlert::TYPE_OVERDUE));
            $user->notify(new InstallmentAlert($installment, InstallmentAlert::TYPE_PAID));
        }

        // 11. DocumentUploaded — gray, no email
        $user->notify(new DocumentUploaded($contract, 'Signed_Contract_v2.pdf', 'Contracts Officer'));

        // 12. UserActionNotification — generic system
        $user->notify(new UserActionNotification([
            'subject'    => 'System Maintenance Scheduled',
            'subtype'    => 'system_alert',
            'message'    => 'Scheduled maintenance will occur on Friday at 2:00 AM.',
            'action_url' => url('/dashboard'),
            'icon'       => 'bell',
            'color'      => 'gray',
            'priority'   => 'low',
        ]));

        $this->command->info('✓ 12 demo notifications sent successfully!');
    }
}
