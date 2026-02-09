<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;

class Grouping extends Model
{
    public $table = 'GNR.Grouping';
    public $connection = 'sqlsrv';
    public $fillable = ['GroupingID'];
}
