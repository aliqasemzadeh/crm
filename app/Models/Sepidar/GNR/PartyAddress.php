<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;

class PartyAddress extends Model
{
    public $table = 'GNR.PartyAddress';
    public $connection = 'sqlsrv';
    public $primaryKey = 'PartyAddressId';
}
