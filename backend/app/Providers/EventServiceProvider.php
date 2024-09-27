<?php

namespace App\Providers;

use App\Events\LateReply;
use App\Listeners\LateReplyResponse;
use App\Listeners\SendVerificationEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendVerificationEmail::class,
        ],

        LateReply::class => [
            LateReplyResponse::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
