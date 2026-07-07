<?php

namespace Khody2012\LaravelAmiToolkit;

use Exception;

class AmiEventListener
{
    protected AmiClient $client;
    protected array $callbacks = [];
    protected bool $listening = false;

    public function __construct(AmiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Register a callback for a specific AMI event
     *
     * @param string $eventName
     * @param callable $callback
     * @return $this
     */
    public function on(string $eventName, callable $callback): self
    {
        $this->callbacks[$eventName][] = $callback;
        return $this;
    }

    /**
     * Start listening to AMI events
     *
     * @return void
     * @throws Exception
     */
    public function listen(): void
    {
        if (!$this->client->connect()) {
            throw new Exception("Could not connect to AMI.");
        }

        $this->listening = true;

        while ($this->listening) {
            $data = $this->readEvent();

            if ($data) {
                $event = $this->parseEvent($data);
                \Log::info('AMI Event Received', $event);
                if (isset($event['Event']) && isset($this->callbacks[$event['Event']])) {
                    foreach ($this->callbacks[$event['Event']] as $callback) {
                        call_user_func($callback, $event);
                    }
                }
                if (
                    config('ami.broadcast') &&
                    $event['Event'] === 'Newchannel'
                ) {
                    event(new CallStarted($event));
                }
            }
        }
    }

    /**
     * Stop listening to events
     */
    public function stop(): void
    {
        $this->listening = false;
        $this->client->disconnect();
    }

    /**
     * Read raw event data from socket
     *
     * @return string|null
     */
    protected function readEvent(): ?string
    {
        $data = '';
        while (($line = fgets($this->client->getSocket())) !== false) {
            $data .= $line;
            if (trim($line) === '') {
                break;
            }
        }
        return $data ?: null;
    }

    /**
     * Parse raw event string into associative array
     *
     * @param string $rawEvent
     * @return array
     */
    protected function parseEvent(string $rawEvent): array
    {
        $lines = explode("\r\n", trim($rawEvent));
        $event = [];

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $event[trim($key)] = trim($value);
            }
        }

        return $event;
    }
}
