<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EnforceSessionLimit
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        $maxSessions = match ($user->role) {
            'Admin' => 1,
            'Head' => 1,
            'Officer' => 1,
            // default => 2,
        };

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        if ($sessions->count() > $maxSessions) {
            // delete oldest sessions
            $sessions
                ->slice($maxSessions)
                ->each(fn ($session) =>
                    DB::table('sessions')->where('id', $session->id)->delete()
                );
        }
    }
}
