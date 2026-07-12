<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiNewCalleridEvent
{
    use Dispatchable, SerializesModels;

    public string $uniqueId;
    public string $channel;
    public ?string $callerIdNum;
    public ?string $callerIdName;
    public ?string $context;
    public ?string $exten;

    public function __construct(array $data)
    {
        $this->uniqueId     = $data['Uniqueid']      ?? '';
        $this->channel      = $data['Channel']       ?? '';
        $this->callerIdNum  = $data['CallerIDNum']   ?? $data['CallerID'] ?? null;
        $this->callerIdName = $data['CallerIDName']  ?? null;
        $this->context      = $data['Context']       ?? null;
        $this->exten        = $data['Exten']         ?? null;
    }

    public function getCallerNumber(): ?string
    {
        return $this->callerIdNum;
    }
}