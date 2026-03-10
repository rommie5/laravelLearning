<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Enhance contract_clauses lifecycle:
 *   1. Replace status CHECK constraint with the new 6-value set
 *   2. Add status_reason, status_changed_by columns
 *   3. Add status_changed_at timestamp
 *
 * All DDL inside an explicit transaction for safety.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {

            // ── 1. Drop the old status CHECK constraint ───────────────────────
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('
                    ALTER TABLE contract_clauses
                    DROP CONSTRAINT IF EXISTS chk_clauses_status_valid
                ');
            }

            // ── 2. Add new audit/lifecycle columns ────────────────────────────
            Schema::table('contract_clauses', function (Blueprint $table) {
                // Reason supplied when status is changed manually
                $table->string('status_reason', 1000)->nullable()->after('status');
                // Who changed the status (null = system/scheduler)
                $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete()->after('status_reason');
                // When the status last changed (null = never changed from initial)
                $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
            });

            // ── 3. Add new CHECK constraint with the full lifecycle set ────────
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT chk_clauses_status_valid
                    CHECK (status IN ('pending','active','expired','completed','terminated','waived'))
                ");
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('
                    ALTER TABLE contract_clauses
                    DROP CONSTRAINT IF EXISTS chk_clauses_status_valid
                ');
            }

            Schema::table('contract_clauses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('status_changed_by');
                $table->dropColumn(['status_reason', 'status_changed_at']);
            });

            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT chk_clauses_status_valid
                    CHECK (status IN ('pending','completed','waived'))
                ");
            }
        });
    }
};
