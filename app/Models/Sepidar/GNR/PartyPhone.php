<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyPhone extends Model
{
    public $table = 'GNR.PartyPhone';
    public $connection = 'sqlsrv';
    public $primaryKey = 'PartyPhoneId';

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'PartyRef', 'PartyId');
    }
}
