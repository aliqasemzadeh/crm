<?php

namespace App\Models\Voip;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phone extends Model
{
    protected $connection = 'voip';

    protected $fillable = [
        'party_id',
        'party_phone_id',
        'is_manual',
        'number',
        'name',
        'name_latin',
    ];

    protected function casts(): array
    {
        return [
            'is_manual' => 'boolean',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id', 'PartyId');
    }

    public function partyPhone(): BelongsTo
    {
        return $this->belongsTo(PartyPhone::class, 'party_phone_id', 'PartyPhoneId');
    }
}
