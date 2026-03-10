<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Parties Information
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('type'); // Client, Contractor, Guarantor
            $table->string('name');
            $table->string('reg_number')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // 2. Structured Clauses
        Schema::create('clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('number'); // 4.2, 7.1
            $table->string('title');
            $table->string('category'); // Termination, Payment, etc.
            $table->text('text');
            $table->boolean('is_critical')->default(false);
            $table->boolean('is_financial_impact')->default(false);
            $table->integer('version')->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 3. Payment Structure (Installments)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, paid, overdue
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('proof_file_path')->nullable();
            $table->timestamps();
        });

        // 4. Performance Security Tracking
        Schema::create('performance_securities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('type'); // Bank Guarantee, Insurance, Cash
            $table->decimal('amount', 15, 2);
            $table->string('issuing_bank')->nullable();
            $table->date('start_date');
            $table->date('expiry_date');
            $table->string('file_path')->nullable();
            $table->string('status')->default('valid'); // valid, expired, claimed, released
            $table->timestamps();
        });

        // 5. Document Management
        Schema::create('contract_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('type'); // PDF, Annex, Addendum
            $table->string('file_path');
            $table->string('file_hash')->nullable();
            $table->integer('version')->default(1);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        // 6. Export Tracking
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('role');
            $table->string('export_type'); // PDF, CSV, Excel
            $table->foreignId('contract_id')->nullable()->constrained();
            $table->string('priority_level')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->boolean('approval_granted')->default(false);
            $table->timestamp('exported_at');
            $table->string('ip_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_logs');
        Schema::dropIfExists('contract_documents');
        Schema::dropIfExists('performance_securities');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('clauses');
        Schema::dropIfExists('parties');
    }
};
