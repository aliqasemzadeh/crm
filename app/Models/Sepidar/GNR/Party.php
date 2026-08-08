<?php

namespace App\Models\Sepidar\GNR;

use App\Models\Sepidar\ACC\DL;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    public $table = 'GNR.Party';
    public $connection = 'sqlsrv';
    public $primaryKey = 'PartyId';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'int';

    public function dl(): BelongsTo
    {
        return $this->belongsTo(DL::class, 'DLRef', 'DLId');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'CustomerPartyRef', 'PartyId');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class, 'PartyRef', 'PartyId');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(PartyPhone::class, 'PartyRef', 'PartyId');
    }

    public function partyRelateds(): HasMany
    {
        return $this->hasMany(PartyRelated::class, 'PartyRef', 'PartyId');
    }
}
