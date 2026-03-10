<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractInstallment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Roles ──────────────────────────────────────────────────
        $adminRole   = Role::firstOrCreate(['name' => 'Admin']);
        $headRole    = Role::firstOrCreate(['name' => 'Head']);
        $officerRole = Role::firstOrCreate(['name' => 'Officer']);

        // ── 2. Users ──────────────────────────────────────────────────
        $admin = User::firstOrCreate(['email' => 'admin@cms.com'], [
            'name' => 'System Admin', 'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $admin->syncRoles([$adminRole]);

        $head = User::firstOrCreate(['email' => 'head@cms.com'], [
            'name' => 'Department Head', 'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $head->syncRoles([$headRole]);

        $officer = User::firstOrCreate(['email' => 'officer@cms.com'], [
            'name' => 'Contracts Officer', 'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $officer->syncRoles([$officerRole]);

        // ── 3. Sample Contract ────────────────────────────────────────
        if (Contract::where('contract_number', 'CMS-2026-001')->exists()) {
            return; // Safe to re-run
        }

        $signingDate = Carbon::now()->subDays(5);
        $startDate   = Carbon::now();
        $expiry      = Contract::computeExpiry($startDate->toDateString(), 24, 'months');

        $contract = Contract::create([
            'contract_number'       => 'CMS-2026-001',
            'contract_name'         => 'Strategic Housing Development Phase I',
            'awarded_to'            => 'Kimo Construction Group',
            'contract_site'         => 'Zone B-14, North Sector',
            'priority_level'        => Contract::PRIORITY_HIGH,
            'contract_signing_date' => $signingDate->toDateString(),
            'start_date'            => $startDate->toDateString(),
            'duration_value'        => 24,
            'duration_unit'         => 'months',
            'expiry_date'           => $expiry->toDateString(),
            'notes'                 => 'All works must comply with municipal safety code 2026-MSC-14.',
            'status'                => Contract::STATUS_ACTIVE,
            'created_by'            => $officer->id,
            'approved_by'           => $head->id,
            'approved_at'           => now(),
        ]);

        // ── 4. Compliance Clauses ─────────────────────────────────────
        // Performance Security: due signing_date + 30 days
        // Must be BEFORE expiry — signing_date + 30 days = ~25 days before expiry (safe)
        $psDueDate = ContractClause::computeDueDate(
            ContractClause::TYPE_PERFORMANCE_SECURITY,
            $signingDate->toDateString(),
            $expiry->toDateString(),
            30
        );

        // Handover: due expiry_date + 60 days (must be AFTER expiry AND after PS)
        $hoDueDate = ContractClause::computeDueDate(
            ContractClause::TYPE_HANDOVER,
            $signingDate->toDateString(),
            $expiry->toDateString(),
            60
        );

        ContractClause::create([
            'contract_id'         => $contract->id,
            'clause_type'         => ContractClause::TYPE_PERFORMANCE_SECURITY,
            'reference_date_type' => ContractClause::REF_SIGNING,
            'period_days'         => 30,
            'due_date'            => $psDueDate->toDateString(),
            'status'              => ContractClause::STATUS_PENDING,
            'created_by'          => $officer->id,
        ]);

        ContractClause::create([
            'contract_id'         => $contract->id,
            'clause_type'         => ContractClause::TYPE_HANDOVER,
            'reference_date_type' => ContractClause::REF_EXPIRY,
            'period_days'         => 60,
            'due_date'            => $hoDueDate->toDateString(),
            'status'              => ContractClause::STATUS_PENDING,
            'created_by'          => $officer->id,
        ]);

        // ── 5. Installments (quarterly, within start_date → expiry) ───
        // 5 installments: months 3, 6, 9, 12, 18 — all within 24-month contract
        foreach ([3, 6, 9, 12, 18] as $idx => $month) {
            ContractInstallment::create([
                'contract_id'    => $contract->id,
                'installment_no' => $idx + 1,
                'amount'         => 50_000_000.00,
                'due_date'       => $startDate->copy()->addMonths($month)->toDateString(),
                'paid_status'    => $idx === 0 ? ContractInstallment::STATUS_PAID : ContractInstallment::STATUS_PENDING,
                'paid_at'        => $idx === 0 ? now() : null,
                'created_by'     => $officer->id,
            ]);
        }
    }
}
