<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class CallStarted implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        // You can change 'calls' to something dynamic if needed
        return new Channel('calls');
    }

    public function broadcastAs(): string
    {
        return 'call.started';
    }
}
