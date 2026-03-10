<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for fast "get my notifications" + unread lookups on PostgreSQL
        DB::statement(
            "CREATE INDEX IF NOT EXISTS notif_user_read_idx
             ON notifications (notifiable_type, notifiable_id, read_at)"
        );

        // Expression index on the JSON data->>'type' column for fast type-based filtering
        DB::statement(
            "CREATE INDEX IF NOT EXISTS notif_data_type_idx
             ON notifications ((data->>'type'))"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS notif_user_read_idx');
        DB::statement('DROP INDEX IF EXISTS notif_data_type_idx');
    }
};
