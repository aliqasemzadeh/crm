<?php

namespace App\Models\Sepidar\FMK;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    public $table = 'FMK.FiscalYear';
    public $connection = 'sqlsrv';
    public $primaryKey = 'FiscalYearID';
}
