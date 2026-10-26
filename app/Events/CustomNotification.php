<?php

namespace App\Events; 

use \App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CustomNotification implements ShouldBroadcast
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
        $notification = $this->notification;
        return [
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'sender' => $this->notification->sender,
            'id' => $this->notification->id,
        ];
    }

    public function broadcastAs()
    {
        return 'notification';
    }

    public function broadcastOn()
    {
        //debugger($this->notification->channel);
        return new Channel($this->notification->channel);
    }
}
