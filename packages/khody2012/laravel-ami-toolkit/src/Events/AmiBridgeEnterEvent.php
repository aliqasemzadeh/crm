<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiBridgeEnterEvent
{
    use Dispatchable, SerializesModels;

    public string $bridgeUniqueid;
    public string $channel;
    public string $linkedId;

    public function __construct(array $data)
    {
        $this->bridgeUniqueid = $data['BridgeUniqueid'] ?? '';
        $this->channel        = $data['Channel']        ?? '';
        $this->linkedId       = $data['Linkedid']       ?? $data['Uniqueid'] ?? '';
    }
}