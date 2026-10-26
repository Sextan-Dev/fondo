<?php

namespace App\Events; 
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeploymentNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $notification;

    /** Create a new event instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    public function broadcastWith()
    {                
        return [ 
            'type' => $this->notification->type,
            'message' => $this->notification->message
        ];
    }

    public function broadcastAs()
    {
        return 'newDeployment';
    }

    public function broadcastOn()
    {
        return new Channel('deployment');
    }
}
