<?php

namespace App\Models\Sepidar\SLS;

use App\Livewire\Panels\Accounting\Invoice\Index;
use App\Models\Sepidar\FMK\User;
use App\Models\Sepidar\GNR\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Invoice extends Model
{
    public $table = 'SLS.Invoice';
    public $connection = 'sqlsrv';
    public $primaryKey = 'InvoiceId';

    protected static function booted()
    {
        static::deleted(function ($invoice) {
            Index::clearCache();
            static::clearUserStatsCache($invoice->Creator);
        });

        static::created(function ($invoice) {
            Index::clearCache();
            static::clearUserStatsCache($invoice->Creator);
        });

        static::updated(function ($invoice) {
            Index::clearCache();
            static::clearUserStatsCache($invoice->Creator);

            if ($invoice->wasChanged('Creator')) {
                static::clearUserStatsCache($invoice->getOriginal('Creator'));
            }
        });
    }

    protected static function clearUserStatsCache(?int $creatorId): void
    {
        if (! $creatorId) {
            return;
        }

        $fiscalYearRef = config('sepidar.FiscalYearRef');
        Cache::forget("accounting_user_invoice_stats_{$creatorId}_{$fiscalYearRef}");
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'CustomerPartyRef', 'PartyId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'InvoiceRef', 'InvoiceId');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Creator', 'UserID');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'LastModifier', 'UserID');
    }
}
