<?php

namespace Khody2012\LaravelAmiToolkit;

use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\EventLoop\Loop;
use RuntimeException;

class AmiConnection
{
    protected array $config;
    protected LoopInterface $loop;
    protected Connector $connector;
    protected ?ConnectionInterface $connection = null;
    protected bool $isConnected = false;
    protected string $buffer = '';
    protected array $pendingActions = [];
    protected ?TimerInterface $heartbeatTimer = null;
    protected int $reconnectAttempts = 0;

    public function __construct(array $config, LoopInterface $loop = null)
    {
        $this->config = $config;
        $this->loop = $loop ?? Loop::get();
        $this->connector = new Connector($this->loop);
    }

    public function connect(): PromiseInterface
    {
        $uri = "tcp://{$this->config['host']}:{$this->config['port']}";

        return $this->connector->connect($uri)->then(function (ConnectionInterface $conn) {
            $this->connection = $conn;
            $this->isConnected = true;
            $this->setupHandlers();
            $this->login();
            Log::info("AMI connected to {$this->config['host']}:{$this->config['port']}");
            return $this;
        }, function ($err) {
            Log::error("AMI connect failed: " . $err->getMessage());
            throw $err;
        });
    }

    protected function login(): void
    {
        $this->sendAction('Login', [
            'Username' => $this->config['username'],
            'Secret'   => $this->config['secret'],
            'Events'   => 'on',
        ], function ($response) {
            if (stripos($response['Response'] ?? '', 'Success') === false) {
                throw new RuntimeException("AMI Login failed: " . ($response['Message'] ?? 'Unknown error'));
            }

            Log::info('AMI Login successful');
            $this->startHeartbeat();
        });
    }

    public function sendAction(string $action, array $params = [], ?callable $onResponse = null): PromiseInterface
    {
        return $this->ensureConnected()->then(function () use ($action, $params, $onResponse) {
            $actionId = uniqid('act_', true);
            $params['ActionID'] = $actionId;

            $message = $this->buildMessage($action, $params);

            $this->connection->write($message);

            if ($onResponse) {
                $this->pendingActions[$actionId] = $onResponse;
            }

            return \React\Promise\resolve(true);
        });
    }

    public function ensureConnected(): PromiseInterface
    {
        if ($this->isConnected && $this->connection !== null) {
            return \React\Promise\resolve($this);
        }

        Log::info('اتصال AMI قطع است، تلاش مجدد...');

        return $this->connect();
    }

    protected function setupHandlers(): void
    {
        $this->connection->on('data', function ($chunk) {
            $this->buffer .= $chunk;
            $this->processBuffer();
        });

        $this->connection->on('close', fn() => $this->onDisconnect('closed'));
        $this->connection->on('error', fn($err) => $this->onDisconnect($err->getMessage()));
    }

    protected function processBuffer(): void
    {
        while (($pos = strpos($this->buffer, "\r\n\r\n")) !== false) {
            $raw = substr($this->buffer, 0, $pos);
            $this->buffer = substr($this->buffer, $pos + 4);

            $message = $this->parseRaw($raw);

            if (!$message) {
                continue;
            }

            if (isset($message['Response'], $message['ActionID'], $this->pendingActions[$message['ActionID']])) {
                $callback = $this->pendingActions[$message['ActionID']];
                unset($this->pendingActions[$message['ActionID']]);
                $callback($message);

                continue;
            }

            if (!isset($message['Event'])) {
                continue;
            }

            $event = $message;
            $eventName = $event['Event'];

            if (isset($event['ActionID']) && isset($this->pendingActions[$event['ActionID']])) {
                $callback = $this->pendingActions[$event['ActionID']];
                unset($this->pendingActions[$event['ActionID']]);
                $callback($event);
                continue;
            }

            switch ($eventName) {
                case 'Newchannel':
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiNewchannelEvent($event));
                    break;

                case 'NewCallerid':
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiNewCalleridEvent($event));
                    break;

                case 'AgentCalled':
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiAgentCalledEvent($event));
                    break;

                case 'AgentConnect':
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiAgentConnectEvent($event));
                    break;

                case 'BridgeEnter':
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiBridgeEvent($event));
                    break;

                case 'Hangup':
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiHangupEvent($event));
                    break;

                default:
                    if (config('ami.log_unknown_events', true)) {
                        Log::debug("AMI Event: {$eventName}", $event);
                    }
                    break;
            }
        }
    }

    protected function parseRaw(string $raw): ?array
    {
        $lines = explode("\r\n", $raw);
        $data = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) continue;
            [$k, $v] = explode(':', $line, 2);
            $data[trim($k)] = trim($v);
        }
        return $data ?: null;
    }

    protected function buildMessage(string $action, array $params): string
    {
        $msg = "Action: $action\r\n";
        foreach ($params as $k => $v) {
            $msg .= "$k: $v\r\n";
        }
        return $msg . "\r\n";
    }

    protected function onDisconnect(string $reason): void
    {
        $this->isConnected = false;
        $this->connection = null;

        $delay = min(60, pow(2, $this->reconnectAttempts));
        $this->reconnectAttempts++;

        $this->loop->addTimer($delay, function () {
            $this->connect()->then(function () {
                $this->reconnectAttempts = 0;
            })->otherwise(function ($e) {
                Log::error("Reconnect failed: " . $e->getMessage());
            });
        });
    }

    public function disconnect(): void
    {
        if (isset($this->heartbeatTimer)) {
            $this->loop->cancelTimer($this->heartbeatTimer);
            $this->heartbeatTimer = null;
        }

        $this->connection?->end();
        $this->connection = null;
        $this->isConnected = false;
        $this->pendingActions = [];
        $this->buffer = '';
    }

    public function startHeartbeat(): void
    {
        if (isset($this->heartbeatTimer)) {
            $this->loop->cancelTimer($this->heartbeatTimer);
        }

        $interval = config('ami.heartbeat_interval', 30);

        $this->heartbeatTimer = $this->loop->addPeriodicTimer($interval, function () {
            if (!$this->isConnected || $this->connection === null) {
                $this->connect();
                return;
            }

            $this->sendAction('Ping', [], function ($response) {
                if (stripos($response['Response'] ?? '', 'Success') !== false) {
                } else {
                    $this->onDisconnect('Ping failed');
                }
            });
        });

    }

    public function isConnected(): bool
    {
        return $this->isConnected && $this->connection !== null;
    }
}