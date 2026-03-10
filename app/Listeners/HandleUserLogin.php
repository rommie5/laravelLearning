<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;


class HandleUserLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        $sessionId = session()->getId();
        $maxSessions = config('session_limits.max_sessions_per_user');

        // Store current session
        DB::table('sessions')
            ->where('id', $sessionId)
            ->update(['user_id' => $user->id]);

        // Fetch user sessions (newest first)
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();

        // Enforce limit
        if ($sessions->count() > $maxSessions) {
            $sessions
                ->slice($maxSessions)
                ->each(fn ($session) =>
                    DB::table('sessions')->where('id', $session->id)->delete()
                );
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }
}