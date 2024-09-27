<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefreshSessionKey implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $hsn;
    public $session_key;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $hsn, string $sessionKey)
    {
        $this->hsn = $hsn;
        $this->session_key = $sessionKey;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['Station.'.$this->hsn];
    }

    public function broadcastAs()
    {
        return 'refresh-session-key';
    }
}
