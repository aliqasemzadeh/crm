<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiAgentConnectEvent
{
    use Dispatchable, SerializesModels;

    public string $uniqueId;
    public string $queue;
    public string $member;
    public string $memberName;
    public int $holdTime;
    public int $ringTime;
    public ?string $bridgedChannel;

    public function __construct(array $data)
    {
        $this->uniqueId       = $data['Uniqueid']       ?? '';
        $this->queue          = $data['Queue']          ?? '';
        $this->member         = $data['Member']         ?? '';
        $this->memberName     = $data['MemberName']     ?? '';
        $this->holdTime       = (int) ($data['HoldTime'] ?? 0);
        $this->ringTime       = (int) ($data['RingTime'] ?? 0);
        $this->bridgedChannel = $data['BridgedChannel'] ?? null;
    }

    public function getAgentExtension(): string
    {
        // مثلاً SIP/109 → 109
        return preg_replace('/^SIP\//', '', $this->memberName);
    }
}