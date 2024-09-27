<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogoutUser implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $response;
    private $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $message='12AM Reset. Logging out...')
    {
        $this->userId = $userId;
        $this->response = ['message' => $message];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['App.User.' . $this->userId];
    }

    public function broadcastAs() 
    {
        return 'logout';
    }
}
