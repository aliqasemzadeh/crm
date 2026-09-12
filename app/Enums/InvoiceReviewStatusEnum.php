<?php

namespace App\Enums;

enum InvoiceReviewStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('app.invoice_review_status_pending'),
            self::APPROVED => __('app.invoice_review_status_approved'),
            self::REJECTED => __('app.invoice_review_status_rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::APPROVED => 'emerald',
            self::REJECTED => 'red',
        };
    }
}
