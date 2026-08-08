<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;

class DeliveryLocation extends Model
{
    public $table = 'GNR.DeliveryLocation';

    public $connection = 'sqlsrv';

    public $primaryKey = 'DeliveryLocationID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'DeliveryLocationID',
        'Title',
        'Title_En',
        'Version',
        'Creator',
        'CreationDate',
        'LastModifier',
        'LastModificationDate',
    ];
}
