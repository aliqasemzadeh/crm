<?php

namespace App\Models\Sepidar\SLS;

use App\Models\Sepidar\INV\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends Model
{
    public $table = 'SLS.InvoiceItem';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceItemId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceItemId',
        'InvoiceRef',
        'RowID',
        'ItemRef',
        'TracingRef',
        'StockRef',
        'Quantity',
        'SecondaryQuantity',
        'Fee',
        'Price',
        'PriceInBaseCurrency',
        'Discount',
        'DiscountInBaseCurrency',
        'DiscountItemGroupRef',
        'PriceInfoDiscountRate',
        'PriceInfoPriceDiscount',
        'PriceInfoPercentDiscount',
        'CustomerDiscount',
        'CustomerDiscountRate',
        'AggregateAmountDiscountRate',
        'AggregateAmountPriceDiscount',
        'AggregateAmountPercentDiscount',
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
        'QuotationItemRef',
        'OrderItemRef',
        'Description',
        'Description_En',
        'DiscountInvoiceItemRef',
        'ProductPackRef',
        'ProductPackQuantity',
        'BankFeeForCurrencySale',
        'BankFeeForCurrencySaleInBaseCurrency',
        'IsAggregateDiscountInvoiceItem',
        'TaxPayerCurrencyPurchaseRate',
        'NetPrice',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'InvoiceRef', 'InvoiceId');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'ItemRef', 'ItemID');
    }

    public function additionFactors(): HasMany
    {
        return $this->hasMany(InvoiceItemAdditionFactor::class, 'InvoiceItemRef', 'InvoiceItemId');
    }
}
