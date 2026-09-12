<?php

namespace App\Models\Crm;

use App\Enums\InvoiceReviewStatusEnum;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReview extends Model
{
    protected $table = 'crm_invoice_reviews';

    protected $fillable = [
        'sepidar_invoice_id',
        'invoice_number',
        'customer_party_ref',
        'customer_name',
        'invoice_date',
        'invoice_net_price',
        'status',
        'balance_snapshot',
        'stock_snapshot',
        'snapshot_at',
        'accounting_reviewed_by',
        'accounting_reviewed_at',
        'accounting_note',
        'warehouse_status',
        'warehouse_handled_by',
        'warehouse_handled_at',
        'warehouse_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceReviewStatusEnum::class,
            'balance_snapshot' => 'array',
            'stock_snapshot' => 'array',
            'invoice_date' => 'datetime',
            'invoice_net_price' => 'decimal:2',
            'snapshot_at' => 'datetime',
            'accounting_reviewed_at' => 'datetime',
            'warehouse_handled_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'sepidar_invoice_id', 'InvoiceId');
    }

    public function accountingReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounting_reviewed_by');
    }

    public function warehouseHandler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_handled_by');
    }

    public function isPending(): bool
    {
        return $this->status === InvoiceReviewStatusEnum::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === InvoiceReviewStatusEnum::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === InvoiceReviewStatusEnum::REJECTED;
    }
}
