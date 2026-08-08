<?php

namespace App\Models\Sepidar\SLS;

use App\Livewire\Panels\Accounting\Invoice\Index;
use App\Models\Sepidar\ACC\Voucher;
use App\Models\Sepidar\FMK\User;
use App\Models\Sepidar\GNR\DeliveryLocation;
use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyAddress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Invoice extends Model
{
    public $table = 'SLS.Invoice';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceId',
        'QuotationRef',
        'OrderRef',
        'CustomerPartyRef',
        'CustomerRealName',
        'CustomerRealName_En',
        'SaleTypeRef',
        'PartyAddressRef',
        'Number',
        'Date',
        'CurrencyRef',
        'SLRef',
        'DeliveryLocationRef',
        'State',
        'Price',
        'PriceInBaseCurrency',
        'Discount',
        'DiscountInBaseCurrency',
        'Addition',
        'AdditionInBaseCurrency',
        'Tax',
        'TaxInBaseCurrency',
        'Duty',
        'DutyInBaseCurrency',
        'AdditionFactor_VatEffective',
        'AdditionFactorInBaseCurrency_VatEffective',
        'AdditionFactor_VatIneffective',
        'AdditionFactorInBaseCurrency_VatIneffective',
        'NetPriceInBaseCurrency',
        'Rate',
        'Version',
        'Creator',
        'CreationDate',
        'LastModifier',
        'LastModificationDate',
        'FiscalYearRef',
        'VoucherRef',
        'ShouldControlCustomerCredit',
        'Guid',
        'BaseOnInventoryDelivery',
        'AgreementRef',
        'TaxPayerBillIssueDateTime',
        'SettlementType',
        'Description',
        'SignatureRef',
        'NetPrice',
    ];

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

    public function address(): BelongsTo
    {
        return $this->belongsTo(PartyAddress::class, 'PartyAddressRef', 'PartyAddressId');
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class, 'DeliveryLocationRef', 'DeliveryLocationID');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'InvoiceRef', 'InvoiceId');
    }

    public function brokers(): HasMany
    {
        return $this->hasMany(InvoiceBroker::class, 'InvoiceRef', 'InvoiceId');
    }

    public function commissionBrokers(): HasMany
    {
        return $this->hasMany(InvoiceCommissionBroker::class, 'InvoiceRef', 'InvoiceId');
    }

    public function receiptInfos(): HasMany
    {
        return $this->hasMany(InvoiceReceiptInfo::class, 'InvoiceRef', 'InvoiceId');
    }

    public function receiptChequeInfos(): HasMany
    {
        return $this->hasMany(InvoiceReceiptChequeInfo::class, 'InvoiceRef', 'InvoiceId');
    }

    public function receiptPosInfos(): HasMany
    {
        return $this->hasMany(InvoiceReceiptPosInfo::class, 'InvoiceRef', 'InvoiceId');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Creator', 'UserID');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'LastModifier', 'UserID');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'VoucherRef', 'VoucherId');
    }

    public function saleType(): BelongsTo
    {
        return $this->belongsTo(SaleType::class, 'SaleTypeRef', 'SaleTypeId');
    }
}
