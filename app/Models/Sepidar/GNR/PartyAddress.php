<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyAddress extends Model
{
    public $table = 'GNR.PartyAddress';
    public $connection = 'sqlsrv';
    public $primaryKey = 'PartyAddressId';
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'PartyRef', 'PartyId');
    }
}
