<?php

namespace App\Models\Crm;

use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceEditWatermark extends Model
{
    protected $table = 'crm_invoice_edit_watermarks';

    protected $fillable = [
        'sepidar_invoice_id',
        'last_modification_date',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_modification_date' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'sepidar_invoice_id', 'InvoiceId');
    }
}
