<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Canonical support tables:
 *   - contract_clauses:    Optional time-bound compliance obligations
 *   - contract_installments: Dynamic payment schedule
 *
 * Includes:
 *   - DB-level CHECK constraints (period_days > 0, amount > 0, clause_type, status values)
 *   - UNIQUE constraints (contract_id + clause_type, contract_id + installment_no)
 *   - Composite indexes for dashboard filtering performance
 *   - Partial indexes (PostgreSQL) for fast scheduler queries on pending items
 */
return new class extends Migration 
{
    public function up(): void
    {
        // ╔══════════════════════════════════════════════╗
        // ║  CONTRACT CLAUSES                            ║
        // ╚══════════════════════════════════════════════╝
        if (!Schema::hasTable('contract_clauses')) {
            Schema::create('contract_clauses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')
                    ->constrained('contracts')
                    ->onDelete('cascade');

                // What type of obligation this clause tracks
                // e.g. performance_security, handover, (future types)
                $table->string('clause_type', 50);

                // Which contract date this clause is measured from
                // performance_security → signing_date
                // handover            → expiry_date
                $table->string('reference_date_type', 20);

                // Days after the reference date — user-defined
                $table->integer('period_days')->nullable();

                // System-computed and stored at creation
                $table->date('due_date')->nullable();

                $table->string('status', 20)->default('pending');
                $table->timestamp('completed_at')->nullable();

                $table->foreignId('created_by')->constrained('users');

                $table->timestamps();
            });

            // ── CHECK constraints ─────────────────────────────────────
            // SQLite does not support ALTER TABLE ... ADD CONSTRAINT after creation
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT chk_clauses_period_days_positive
                    CHECK (period_days IS NULL OR period_days > 0)
                ");

                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT chk_clauses_clause_type_valid
                    CHECK (clause_type IN ('performance_security', 'handover'))
                ");

                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT chk_clauses_reference_date_type_valid
                    CHECK (reference_date_type IN ('signing_date', 'expiry_date'))
                ");

                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT chk_clauses_status_valid
                    CHECK (status IN ('pending', 'completed', 'waived'))
                ");

                // ── UNIQUE (one of each clause type per contract) ─────────
                DB::statement("
                    ALTER TABLE contract_clauses
                    ADD CONSTRAINT unique_contract_clause_type
                    UNIQUE (contract_id, clause_type)
                ");
            }

            // ── Composite index: filter contract obligations by status ─
            DB::statement("CREATE INDEX IF NOT EXISTS idx_clauses_contract_status ON contract_clauses (contract_id, status)");

            // ── Standard index: scheduler scans by due_date ───────────
            DB::statement("CREATE INDEX IF NOT EXISTS idx_clauses_due_date ON contract_clauses (due_date)");

            // ── PARTIAL INDEX: fast pending-only scheduler ─
            // SQLite supports WHERE clause in partial indexes since 3.8.9
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_pending_clauses_due
                ON contract_clauses (due_date)
                WHERE status = 'pending'
            ");
        }

        // ╔══════════════════════════════════════════════╗
        // ║  CONTRACT INSTALLMENTS                       ║
        // ╚══════════════════════════════════════════════╝
        if (!Schema::hasTable('contract_installments')) {
            Schema::create('contract_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')
                    ->constrained('contracts')
                    ->onDelete('cascade');

                // Sequential position (1, 2, 3, ...)
                $table->unsignedInteger('installment_no');

                $table->decimal('amount', 15, 2);

                // Must be >= start_date, <= expiry_date (enforced in service layer)
                $table->date('due_date');

                $table->string('paid_status', 20)->default('pending');
                $table->timestamp('paid_at')->nullable();

                $table->foreignId('created_by')->constrained('users');

                $table->timestamps();
            });

            // ── CHECK constraints ─────────────────────────────────────
            // SQLite does not support ALTER TABLE ... ADD CONSTRAINT after creation
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("
                    ALTER TABLE contract_installments
                    ADD CONSTRAINT chk_installments_amount_positive
                    CHECK (amount > 0)
                ");

                DB::statement("
                    ALTER TABLE contract_installments
                    ADD CONSTRAINT chk_installments_paid_status_valid
                    CHECK (paid_status IN ('pending', 'paid', 'overdue'))
                ");

                // ── UNIQUE: no duplicate installment number per contract ──
                DB::statement("
                    ALTER TABLE contract_installments
                    ADD CONSTRAINT unique_contract_installment_no
                    UNIQUE (contract_id, installment_no)
                ");
            }

            // ── Composite index: filter contract payments by status ────
            DB::statement("CREATE INDEX IF NOT EXISTS idx_installments_contract_status ON contract_installments (contract_id, paid_status)");

            // ── Standard index: scheduler scans by due_date ───────────
            DB::statement("CREATE INDEX IF NOT EXISTS idx_installments_due_date ON contract_installments (due_date)");

            // ── PARTIAL INDEX: pending-only scheduler ─────
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_pending_installments_due
                ON contract_installments (due_date)
                WHERE paid_status = 'pending'
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_installments');
        Schema::dropIfExists('contract_clauses');
    }
};
