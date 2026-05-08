<?php

namespace App\Models\Sepidar\GNR;

use App\Models\Voip\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartyPhone extends Model
{
    public $table = 'GNR.PartyPhone';

    public $connection = 'sqlsrv';

    public $primaryKey = 'PartyPhoneId';

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'PartyRef', 'PartyId');
    }

    public function phone(): HasOne
    {
        return $this->hasOne(Phone::class, 'party_phone_id', 'PartyPhoneId');
    }
}
