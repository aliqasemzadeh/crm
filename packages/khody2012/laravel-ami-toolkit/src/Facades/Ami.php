<?php

namespace Khody2012\LaravelAmiToolkit\Facades;

use Illuminate\Support\Facades\Facade;

class Ami extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ami';
    }
}
