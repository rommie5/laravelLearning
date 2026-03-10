<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Canonical contracts table alignment:
 * - Makes all legacy NOT NULL columns nullable (title, reference_number, vendor_name, contract_value)
 * - Adds all spec-compliant columns
 * - Adds DB-level CHECK constraints (duration_unit, duration_value)
 * - Adds proper indexes (expiry_date, status)
 * - Converts status to VARCHAR to support full workflow lifecycle
 */
return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // ── 1. Make legacy NOT NULL columns nullable ───────────────
            // (these columns remain from the original migration — we keep them
            //  to avoid breaking existing data on non-fresh installs)
            $table->string('title')->nullable()->change();
            $table->string('reference_number')->nullable()->change();
            $table->string('vendor_name')->nullable()->change();
            $table->decimal('contract_value', 15, 2)->nullable()->change();

            // ── 2. Add new spec-compliant columns ─────────────────────
            if (!Schema::hasColumn('contracts', 'contract_number')) {
                $table->string('contract_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('contracts', 'contract_name')) {
                $table->string('contract_name')->nullable()->after('contract_number');
            }
            if (!Schema::hasColumn('contracts', 'awarded_to')) {
                $table->string('awarded_to')->nullable()->after('contract_name');
            }
            if (!Schema::hasColumn('contracts', 'contract_site')) {
                $table->string('contract_site')->nullable()->after('awarded_to');
            }
            if (!Schema::hasColumn('contracts', 'priority_level')) {
                $table->string('priority_level')->nullable()->after('contract_site');
            }
            if (!Schema::hasColumn('contracts', 'contract_signing_date')) {
                $table->date('contract_signing_date')->nullable()->after('priority_level');
            }
            if (!Schema::hasColumn('contracts', 'duration_value')) {
                $table->integer('duration_value')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('contracts', 'duration_unit')) {
                $table->string('duration_unit', 10)->nullable()->after('duration_value');
            }
            if (!Schema::hasColumn('contracts', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('duration_unit');
            }
            if (!Schema::hasColumn('contracts', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('contracts', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('contracts', 'termination_reason')) {
                $table->text('termination_reason')->nullable();
            }
            if (!Schema::hasColumn('contracts', 'close_reason')) {
                $table->text('close_reason')->nullable();
            }
        });

        // ── 3. Convert status to VARCHAR (supports full lifecycle) ────
        // SQLite does not support ALTER COLUMN TYPE; only needed for PostgreSQL
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS contracts_status_check");
            DB::statement("ALTER TABLE contracts ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE contracts ALTER COLUMN status SET DEFAULT 'draft'");
        }

        // ── 4. DB-level CHECK constraints ─────────────────────────────
        // SQLite does not support ALTER TABLE ... ADD CONSTRAINT after creation
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE contracts
                ADD CONSTRAINT chk_contracts_duration_value_positive
                CHECK (duration_value IS NULL OR duration_value > 0)
            ");

            DB::statement("
                ALTER TABLE contracts
                ADD CONSTRAINT chk_contracts_duration_unit_valid
                CHECK (duration_unit IS NULL OR duration_unit IN ('weeks', 'months'))
            ");

            DB::statement("
                ALTER TABLE contracts
                ADD CONSTRAINT chk_contracts_priority_level_valid
                CHECK (priority_level IS NULL OR priority_level IN ('low', 'medium', 'high', 'sensitive'))
            ");
        }

        // ── 5. Performance indexes ────────────────────────────────────
        // These accelerate dashboard queries and the expiry alert scheduler
        if (!$this->hasIndex('contracts', 'contracts_expiry_date_index')) {
            DB::statement("CREATE INDEX IF NOT EXISTS contracts_expiry_date_index ON contracts (expiry_date)");
        }
        if (!$this->hasIndex('contracts', 'contracts_status_index')) {
            DB::statement("CREATE INDEX IF NOT EXISTS contracts_status_index ON contracts (status)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS chk_contracts_duration_value_positive");
            DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS chk_contracts_duration_unit_valid");
            DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS chk_contracts_priority_level_valid");
        }
        DB::statement("DROP INDEX IF EXISTS contracts_expiry_date_index");
        DB::statement("DROP INDEX IF EXISTS contracts_status_index");

        Schema::table('contracts', function (Blueprint $table) {
            $toDrop = [
                'contract_number', 'contract_name', 'awarded_to', 'contract_site',
                'priority_level', 'contract_signing_date', 'duration_value',
                'duration_unit', 'expiry_date', 'notes', 'approved_at',
                'termination_reason', 'close_reason',
            ];
            foreach ($toDrop as $col) {
                if (Schema::hasColumn('contracts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::select("
                SELECT name FROM sqlite_master
                WHERE type = 'index' AND tbl_name = ? AND name = ?
            ", [$table, $indexName]);
        }
        else {
            $result = DB::select("
                SELECT indexname FROM pg_indexes
                WHERE tablename = ? AND indexname = ?
            ", [$table, $indexName]);
        }
        return count($result) > 0;
    }
};
