<?php

namespace App\Enums;

enum FollowUpStatusEnum: string
{
    case PENDING = 'pending';           // در انتظار بررسی
    case COMPLETED = 'completed';       // بررسی شده
    case CANCELLED = 'cancelled';       // لغو شده
    case RESCHEDULED = 'rescheduled';   // پیگیری بعدی

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'در انتظار بررسی',
            self::COMPLETED => 'بررسی شده',
            self::CANCELLED => 'لغو شده',
            self::RESCHEDULED => 'پیگیری بعدی',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'orange',
            self::COMPLETED => 'emerald',
            self::CANCELLED => 'red',
            self::RESCHEDULED => 'blue',
        };
    }
}
