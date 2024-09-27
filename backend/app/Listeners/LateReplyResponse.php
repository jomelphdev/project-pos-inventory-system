<?php

namespace App\Listeners;

use App\Events\LateReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LateReplyResponse
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  LateReply  $event
     * @return void
     */
    public function handle(LateReply $event)
    {
        //
    }
}
