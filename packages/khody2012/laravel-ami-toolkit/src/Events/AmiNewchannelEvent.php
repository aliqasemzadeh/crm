<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiNewchannelEvent
{
    use Dispatchable, SerializesModels;

    public string $uniqueId;
    public string $linkedId;
    public string $channel;
    public ?string $callerIdNum;
    public ?string $callerIdName;
    public ?string $context;
    public ?string $exten;
    public string $channelState;
    public string $channelStateDesc;

    public function __construct(array $data)
    {
        $this->uniqueId         = $data['Uniqueid']         ?? '';
        $this->linkedId         = $data['Linkedid']         ?? $this->uniqueId;
        $this->channel          = $data['Channel']          ?? '';
        $this->callerIdNum      = $data['CallerIDNum']      ?? null;
        $this->callerIdName     = $data['CallerIDName']     ?? null;
        $this->context          = $data['Context']          ?? null;
        $this->exten            = $data['Exten']            ?? null;
        $this->channelState     = $data['ChannelState']     ?? '0';
        $this->channelStateDesc = $data['ChannelStateDesc'] ?? 'Down';
    }

    public function isIncoming(): bool
    {
        return !empty($this->callerIdNum) && str_starts_with($this->channel, 'SIP/') && $this->context === 'from-pstn';
    }

    public function getCallerNumber(): ?string
    {
        return $this->callerIdNum;
    }
}