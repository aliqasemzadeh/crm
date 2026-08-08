<?php

namespace App\Models\Sepidar\INV;

use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDeliveryItem extends Model
{
    public $table = 'INV.InventoryDeliveryItem';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InventoryDeliveryItemID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InventoryDeliveryItemID',
        'InventoryDeliveryRef',
        'IsReturn',
        'RowNumber',
        'BaseInvoiceItem',
        'BaseInventoryDeliveryItem',
        'BaseReturnedInvoiceItem',
        'QuotationItemRef',
        'ItemRef',
        'TracingRef',
        'Quantity',
        'SecondaryQuantity',
        'RemainingQuantity',
        'RemainingSecondaryQuantity',
        'SLAccountRef',
        'Price',
        'Description',
        'Description_En',
        'Version',
        'ProductOrderRef',
        'ParityCheck',
        'WeighingRef',
        'ItemRequestItemRef',
        'ItemDescription',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(InventoryDelivery::class, 'InventoryDeliveryRef', 'InventoryDeliveryID');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'ItemRef', 'ItemID');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'BaseInvoiceItem', 'InvoiceItemID');
    }
}
