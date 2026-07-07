<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiBridgeEvent
{
    use Dispatchable, SerializesModels;

    public ?string $bridgeUniqueid;
    public string $channel;
    public ?string $channel1;
    public ?string $channel2;
    public string $linkedId;
    public string $bridgestate;
    public string $bridgetype;

    public function __construct(array $data)
    {
        $this->bridgeUniqueid = $data['BridgeUniqueid'] ?? null;
        $this->channel        = $data['Channel']        ?? '';
        $this->channel1       = $data['Channel1']       ?? null;
        $this->channel2       = $data['Channel2']       ?? null;
        $this->linkedId       = $data['Linkedid']       ?? $data['Uniqueid'] ?? '';
        $this->bridgestate    = $data['Bridgestate']    ?? 'Link';
        $this->bridgetype     = $data['Bridgetype']     ?? 'core';
    }
}