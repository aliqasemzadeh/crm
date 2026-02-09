<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    public $table = 'RPA.Bank';
    public $connection = 'sqlsrv';
}
