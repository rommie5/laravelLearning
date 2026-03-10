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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference_number')->unique();
            $table->text('description')->nullable();
            $table->string('vendor_name');
            $table->decimal('contract_value', 15, 2);
            $table->enum('priority', ['low', 'medium', 'high', 'sensitive'])->default('low');
            $table->enum('status', [
                'draft', 
                'pending_creation', 
                'pending_update', 
                'pending_deletion', 
                'approved', 
                'rejected', 
                'active', 
                'expired'
            ])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
