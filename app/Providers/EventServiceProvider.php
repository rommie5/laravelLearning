<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\HandleUserLogin;
use App\Listeners\HandleUserLogout;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            HandleUserLogin::class,
        ],
        Logout::class => [
            HandleUserLogout::class,
        ],
    ];
}