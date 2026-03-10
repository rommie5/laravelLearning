<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    // Composite index for fast "get my notifications" + unread lookups
    DB::statement(
        "CREATE INDEX IF NOT EXISTS notif_user_read_idx
         ON notifications (notifiable_type, notifiable_id, read_at)"
    );

    // FIX: Add ::jsonb to the data column so the operator works
    DB::statement(
        "CREATE INDEX IF NOT EXISTS notif_data_type_idx
         ON notifications (((data::jsonb)->>'type'))"
    );
}

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS notif_user_read_idx');
        DB::statement('DROP INDEX IF EXISTS notif_data_type_idx');
    }
};
