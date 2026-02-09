<?php

namespace App\Models\Sepidar\GNR;

use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    public $table = 'sepidar_parties';

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
}
