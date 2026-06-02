<?php

namespace App\Providers;

use App\Listeners\RecordLoginActivity;
use App\Listeners\RecordLogoutActivity;
use App\Listeners\RecordFailedLoginActivity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class => [
            RecordLoginActivity::class,
        ],
        Logout::class => [
            RecordLogoutActivity::class,
        ],
        Failed::class => [
            RecordFailedLoginActivity::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
