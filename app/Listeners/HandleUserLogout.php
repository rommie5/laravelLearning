<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;

class HandleUserLogout
{
    public function handle(Logout $event): void
    {
        DB::table('sessions')
            ->where('id', session()->getId())
            ->delete();
    }
}