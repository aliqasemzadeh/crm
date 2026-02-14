<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CurrencyRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'currency_code',
        'rate_date',
        'rate_irr',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_date' => 'date:Y-m-d',
        ];
    }

    public static function getRate($date, $currency = 'USDT')
    {
        $dateString = \Illuminate\Support\Carbon::parse($date)->format('Y-m-d');
        $cacheKey = "currency_rate_{$currency}_{$dateString}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($date, $currency) {
            return self::where('currency_code', $currency)
                ->where('rate_date', '<=', $date)
                ->orderByDesc('rate_date')
                ->value('rate_irr');
        });
    }
}
