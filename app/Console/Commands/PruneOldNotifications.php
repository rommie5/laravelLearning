<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneOldNotifications extends Command
{
    protected $signature   = 'notifications:prune';
    protected $description = 'Delete old notifications: read after 90 days, unread after 180 days.';

    public function handle(): int
    {
        $readCutoff   = now()->subDays(90);
        $unreadCutoff = now()->subDays(180);

        // Delete old read notifications
        $readDeleted = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $readCutoff)
            ->delete();

        // Delete old unread notifications
        $unreadDeleted = DB::table('notifications')
            ->whereNull('read_at')
            ->where('created_at', '<', $unreadCutoff)
            ->delete();

        $total = $readDeleted + $unreadDeleted;

        Log::info('Notifications pruned', [
            'read_deleted'   => $readDeleted,
            'unread_deleted' => $unreadDeleted,
            'total'          => $total,
        ]);

        $this->info("✓ Pruned {$readDeleted} read (>90 days) and {$unreadDeleted} unread (>180 days) notifications.");
        $this->info("Total deleted: {$total}");

        return self::SUCCESS;
    }
}
