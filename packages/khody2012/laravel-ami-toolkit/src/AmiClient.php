<?php

namespace Khody2012\LaravelAmiToolkit;

use Khody2012\LaravelAmiToolkit\AmiConnection;
use React\Promise\PromiseInterface;
use React\Promise\Promise;

class AmiClient
{
    protected AmiConnection $connection;

    public function __construct(AmiConnection $connection)
    {
        $this->connection = $connection;
    }

    public function connect(): PromiseInterface
    {
        return $this->connection->connect();
    }

    public function action(string $action, array $params = [], bool $fireAndForget = false): PromiseInterface
    {
        if ($fireAndForget) {
            return $this->connection->ensureConnected()->then(function () use ($action, $params) {
                $this->connection->sendAction($action, $params);

                return null;
            });
        }

        return new Promise(function ($resolve, $reject) use ($action, $params) {
            $this->connection->sendAction($action, $params, function ($response) use ($resolve, $reject) {
                if (stripos($response['Response'] ?? '', 'Error') !== false) {
                    $reject(new \Exception($response['Message'] ?? 'AMI action failed'));
                } else {
                    $resolve($response);
                }
            });
        });
    }

}