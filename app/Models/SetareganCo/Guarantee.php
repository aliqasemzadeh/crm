<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class Guarantee extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'Guarantee';
    protected $primaryKey = 'Id';
    public $timestamps = false;
}
