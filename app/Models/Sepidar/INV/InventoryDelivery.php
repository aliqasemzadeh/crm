<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDelivery extends Model
{
    public $table = 'INV.InventoryDelivery';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InventoryDeliveryID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InventoryDeliveryID',
        'IsReturn',
        'Type',
        'StockRef',
        'ReceiverDLRef',
        'Number',
        'Date',
        'TotalPrice',
        'AccountingVoucherRef',
        'FiscalYearRef',
        'DestinationStockRef',
        'CreatorForm',
        'Creator',
        'CreationDate',
        'LastModifier',
        'LastModificationDate',
        'Version',
        'Description',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryDeliveryItem::class, 'InventoryDeliveryRef', 'InventoryDeliveryID');
    }
}
