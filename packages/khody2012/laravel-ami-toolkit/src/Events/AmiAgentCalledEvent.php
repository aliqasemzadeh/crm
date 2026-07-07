<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiAgentCalledEvent
{
    use Dispatchable, SerializesModels;

    public string $uniqueId;
    public string $queue;
    public string $agentCalled;
    public string $agentName;
    public string $channelCalling;
    public ?string $destinationChannel;
    public ?string $callerIdNum;
    public ?string $callerIdName;

    public function __construct(array $data)
    {
        $this->uniqueId          = $data['Uniqueid']          ?? '';
        $this->queue             = $data['Queue']             ?? '';
        $this->agentCalled       = $data['AgentCalled']       ?? '';
        $this->agentName         = $data['AgentName']         ?? $this->agentCalled;
        $this->channelCalling    = $data['ChannelCalling']    ?? '';
        $this->destinationChannel = $data['DestinationChannel'] ?? '';
        $this->callerIdNum       = $data['CallerIDNum']       ?? null;
        $this->callerIdName      = $data['CallerIDName']      ?? null;
    }
    public function getAgentExtension(): ?string
    {
        return preg_replace('/^SIP\//i', '', trim($this->agentCalled));
    }

    public function toArray(): array
    {
        return [
            'unique_id'           => $this->uniqueId,
            'queue'               => $this->queue,
            'agent_called'        => $this->agentCalled,
            'agent_name'          => $this->agentName,
            'channel_calling'     => $this->channelCalling,
            'destination_channel' => $this->destinationChannel,
            'caller_id_num'       => $this->callerIdNum,
            'caller_id_name'      => $this->callerIdName,
            'agent_extension'     => $this->getAgentExtension(),
        ];
    }

    public function __toString(): string
    {
        return "AgentCalled: {$this->agentCalled} ({$this->getAgentExtension()}) in queue {$this->queue} - Caller: {$this->callerIdNum}";
    }
}