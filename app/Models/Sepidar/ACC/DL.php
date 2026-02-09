<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;

class DL extends Model
{
    public $table = 'ACC.DL';
    public $connection = 'sqlsrv';
    public $primaryKey = 'DLId';
}
