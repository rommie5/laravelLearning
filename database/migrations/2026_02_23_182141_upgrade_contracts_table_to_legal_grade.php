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
        Schema::table('contracts', function (Blueprint $table) {
            // 1. Identification
            $table->string('contract_type')->nullable()->after('status'); // Fixed Price, T&M, etc.
            $table->string('category')->nullable()->after('contract_type');
            $table->string('project_name')->nullable()->after('category');
            $table->string('department')->nullable()->after('project_name');
            
            // 2. Financial Details
            $table->string('currency', 3)->default('USD')->after('contract_value');
            $table->boolean('vat_included')->default(false);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->decimal('tax_withholding_percentage', 5, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->nullable();
            
            // 3. Key Dates
            $table->date('signing_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('performance_security_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->date('handover_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->integer('notice_period')->default(30); // Days
            $table->integer('grace_period')->default(0); // Days
            
            // 4. Performance Security (Summary Fields)
            $table->string('security_type')->nullable(); // Bank Guarantee, etc.
            $table->decimal('security_amount', 15, 2)->nullable();
            
            // 5. Risk Tracking
            $table->string('risk_level')->default('low'); // Low, Medium, High, Critical
            $table->text('risk_notes')->nullable();
            $table->string('litigation_status')->default('none');
            $table->boolean('breach_recorded')->default(false);
            
            // 6. Metadata
            $table->json('tags')->nullable();
            $table->string('region')->nullable();
            $table->string('sector')->nullable();
            $table->string('funding_source')->nullable();
            $table->string('internal_reference_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'contract_type', 'category', 'project_name', 'department',
                'currency', 'vat_included', 'vat_percentage', 'tax_withholding_percentage', 'net_amount',
                'signing_date', 'effective_date', 'performance_security_expiry_date', 'insurance_expiry_date',
                'warranty_expiry_date', 'handover_date', 'renewal_date', 'notice_period', 'grace_period',
                'security_type', 'security_amount', 'risk_level', 'risk_notes', 'litigation_status',
                'breach_recorded', 'tags', 'region', 'sector', 'funding_source', 'internal_reference_code'
            ]);
        });
    }
};
