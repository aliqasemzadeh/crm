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

    /**
     * تماس داخلی بین دو داخلی کوتاه (تا ۵ رقم، مثل ۱۵↔۲۵ یا ۱۲۰↔۳۴۵) را حذف می‌کند.
     */
    public function scopeExcludeInternalExtensionCalls(Builder $query): Builder
    {
        return $query->whereRaw(
            "NOT (TRIM(src) REGEXP '^[0-9]{1,5}$' AND TRIM(dst) REGEXP '^[0-9]{1,5}$')"
        );
    }

    /**
     * فقط تماس‌های اصلی/خارجی: حداقل یک طرف شماره واقعی (≥۸ رقم) باشد، نه داخلی↔داخلی.
     */
    public function scopeOnlyExternalCalls(Builder $query): Builder
    {
        return $query
            ->excludeInternalExtensionCalls()
            ->where(function (Builder $q): void {
                $q->whereRaw("(TRIM(src) REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(src)) >= 8)")
                    ->orWhereRaw("(TRIM(dst) REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(dst)) >= 8)")
                    ->orWhereRaw("(TRIM(IFNULL(cnum, '')) REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(cnum)) >= 8)");
            });
    }

    public function scopeValidCalldate(Builder $query): Builder
    {
        return $query->where('calldate', '>', '1971-01-01 00:00:00');
    }
}
