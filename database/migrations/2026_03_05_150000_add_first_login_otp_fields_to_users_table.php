<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('otp_required')->default(false)->after('two_factor_enabled');
            $table->string('otp_code')->nullable()->after('otp_required');
            $table->timestamp('otp_sent_at')->nullable()->after('otp_code');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_sent_at');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'otp_required',
                'otp_code',
                'otp_sent_at',
                'otp_expires_at',
                'otp_verified_at',
            ]);
        });
    }
};
