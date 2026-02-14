<?php

namespace App\Models\Sepidar\FMK;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $table = 'FMK.User';
    public $connection = 'sqlsrv';
    protected $primaryKey = 'UserID';
}
