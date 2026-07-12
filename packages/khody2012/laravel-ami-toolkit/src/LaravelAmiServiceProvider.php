<?php

namespace Khody2012\LaravelAmiToolkit;

use Illuminate\Support\ServiceProvider;
use Khody2012\LaravelAmiToolkit\Actions\OriginateCall;

class LaravelAmiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ami.php' => config_path('ami.php'),
        ], 'ami-config');

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ami.php',
            'ami'
        );

        $this->app->singleton(AmiConnection::class, function ($app) {
            return new AmiConnection(config('ami'));
        });

        $this->app->singleton(AmiClient::class, function ($app) {
            $connection = $app->make(AmiConnection::class);
            return new AmiClient($connection);
        });

        $this->app->singleton(AmiService::class, function ($app) {
            return new AmiService($app->make(AmiClient::class));
        });

        $this->app->alias(AmiService::class, 'ami');

        $this->app->singleton(OriginateCall::class,
            function ($app) {
                return new OriginateCall(
                    $app->make(AmiService::class)
                );
            }
        );
        $this->commands([
            \Khody2012\LaravelAmiToolkit\Commands\AmiListenCommand::class,
        ]);
    }
}