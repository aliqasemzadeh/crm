<?php

namespace App\Models\Calender;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Day extends Model
{
    protected $fillable = [
        'date',
        'title',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public static function cacheKey(CarbonInterface|string $date): string
    {
        $dateString = $date instanceof CarbonInterface
            ? $date->toDateString()
            : (string) $date;

        return "calender.holiday.{$dateString}";
    }

    public static function clearHolidayCache(CarbonInterface|string|null $date = null): void
    {
        if ($date !== null) {
            Cache::forget(static::cacheKey($date));

            return;
        }

        // When date is unknown (bulk), forget nothing by pattern — callers pass date when possible.
    }

    public static function isHoliday(?CarbonInterface $date = null): bool
    {
        $day = ($date ?? now())->startOfDay();
        $dateString = $day->toDateString();

        return Cache::remember(static::cacheKey($dateString), 86400, function () use ($dateString) {
            return static::query()->whereDate('date', $dateString)->exists();
        });
    }

    public static function isNonWorkingDay(?CarbonInterface $date = null): bool
    {
        $day = $date ?? now();

        return $day->isFriday() || static::isHoliday($day);
    }

    protected static function booted(): void
    {
        static::saved(function (Day $day) {
            static::clearHolidayCache($day->date);
        });

        static::deleted(function (Day $day) {
            static::clearHolidayCache($day->date);
        });
    }
}
