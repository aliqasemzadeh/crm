<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'Color';
    protected $primaryKey = 'Id';
    public $timestamps = false;
}
