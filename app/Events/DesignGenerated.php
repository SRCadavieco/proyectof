<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DesignGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taskId;
    public $imageUrl;
    public $error;

    /**
     * Create a new event instance.
     *
     * @param string $taskId
     * @param string $imageUrl
     */
    public function __construct(string $taskId, string $imageUrl)
    {
        $this->taskId = $taskId;
        $this->imageUrl = $imageUrl;
        $this->error = func_num_args() > 2 ? func_get_arg(2) : null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('designs.' . $this->taskId);
    }
}