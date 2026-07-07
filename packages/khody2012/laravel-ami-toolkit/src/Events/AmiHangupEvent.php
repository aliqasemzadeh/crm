<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiHangupEvent
{
    use Dispatchable, SerializesModels;

    public string $uniqueId;
    public string $channel;
    public ?string $cause;
    public ?string $causeTxt;

    public function __construct(array $data)
    {
        $this->uniqueId = $data['Uniqueid'] ?? '';
        $this->channel  = $data['Channel']  ?? '';
        $this->cause    = $data['Cause']    ?? null;
        $this->causeTxt = $data['Cause-txt'] ?? null;
    }
}