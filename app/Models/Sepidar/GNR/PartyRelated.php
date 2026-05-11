<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyRelated extends Model
{
    public $table = 'GNR.PartyRelated';

    public $connection = 'sqlsrv';

    public $primaryKey = 'PartyRelatedId';

    public $timestamps = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'PartyRelatedId',
        'PartyRef',
        'IsMain',
        'Name',
        'Post',
        'Name_En',
        'Post_En',
        'Phone',
        'Email',
        'Version',
    ];

    protected function casts(): array
    {
        return [
            'IsMain' => 'integer',
            'Version' => 'integer',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'PartyRef', 'PartyId');
    }
}
