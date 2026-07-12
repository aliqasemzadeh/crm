<?php

namespace Khody2012\LaravelAmiToolkit;

use React\Promise\PromiseInterface;
use React\Promise\Promise;

class AmiService
{
    protected AmiClient $client;

    public function __construct(AmiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Connect to AMI asynchronously.
     *
     * @return PromiseInterface
     */
    public function connect(): PromiseInterface
    {
        return $this->client->connect();
    }

    /**
     * Send a ping command to AMI.
     *
     * @return PromiseInterface<array|null>
     */
    public function ping(): PromiseInterface
    {
        return $this->client->action('Ping');
    }

    /**
     * Originate a call.
     *
     * @param array $params Originate parameters (Channel, Exten, Context, Priority, etc.)
     * @return PromiseInterface<array|null>
     */
    public function originate(array $params): PromiseInterface
    {
        return $this->client->action('Originate', $params);
    }

    /**
     * Send any custom AMI action.
     *
     * @param string $action AMI action name
     * @param array $params Action parameters
     * @param bool $fireAndForget If true, doesn't wait for response
     * @return PromiseInterface<array|null>
     */
    public function action(string $action, array $params = [], bool $fireAndForget = false): PromiseInterface
    {
        return $this->client->action($action, $params, $fireAndForget);
    }

    public function hangup(string $channel, bool $fireAndForget = true): PromiseInterface
    {
        return $this->action('Hangup', ['Channel' => $channel], $fireAndForget);
    }

    public function status(): PromiseInterface
    {
        return $this->action('Status');
    }
}