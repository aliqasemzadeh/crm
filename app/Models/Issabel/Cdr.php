<?php

namespace App\Models\Issabel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Cdr extends Model
{
    protected $connection = 'issabel';

    protected $table = 'cdr';

    public $timestamps = false;

    protected $primaryKey = 'uniqueid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'calldate' => 'datetime',
        ];
    }

    /** تماس داخلی بین دو شماره دو رقمی (مثل ۱۵ به ۲۵) را حذف می‌کند */
    public function scopeExcludeInternalTwoDigitExtensions(Builder $query): Builder
    {
        return $query->whereRaw(
            "NOT (CHAR_LENGTH(TRIM(src)) = 2 AND CHAR_LENGTH(TRIM(dst)) = 2 AND TRIM(src) REGEXP '^[0-9]{2}$' AND TRIM(dst) REGEXP '^[0-9]{2}$')"
        );
    }

    public function scopeValidCalldate(Builder $query): Builder
    {
        return $query->where('calldate', '>', '1971-01-01 00:00:00');
    }
}
