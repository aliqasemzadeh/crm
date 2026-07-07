<?php

namespace Khody2012\LaravelAmiToolkit\Commands;

use Khody2012\LaravelAmiToolkit\AmiConnection;
use Illuminate\Console\Command;
use React\EventLoop\Loop;

class AmiListenCommand extends Command
{
    protected $signature = 'ami:listen';

    public function handle(AmiConnection $connection)
    {
        $connection->connect()->then(function () {
            $this->info('AMI listener started. Waiting for events...');
        })->otherwise(function ($e) {
            $this->error('Connection failed: ' . $e->getMessage());
        });

        Loop::run();
    }
}