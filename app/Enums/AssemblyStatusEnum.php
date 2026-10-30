<?php

namespace App\Enums;

enum AssemblyStatusEnum: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case ReturnedWarranty = 'returned_warranty';
    case Cancelled = 'cancelled';

    public function translationKey(): string
    {
        return match ($this) {
            self::Pending => 'assembly_status_pending',
            self::InProgress => 'assembly_status_in_progress',
            self::Ready => 'assembly_status_ready',
            self::Delivered => 'assembly_status_delivered',
            self::ReturnedWarranty => 'assembly_status_returned_warranty',
            self::Cancelled => 'assembly_status_cancelled',
        };
    }

    public function label(): string
    {
        return __('app.'.$this->translationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::InProgress => 'amber',
            self::Ready => 'green',
            self::Delivered => 'emerald',
            self::ReturnedWarranty => 'indigo',
            self::Cancelled => 'red',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            self::cases()
        );
    }

    public static function tryFromSafe(?string $value): self
    {
        if ($value === null) {
            return self::Pending;
        }

        return self::tryFrom($value) ?? self::Pending;
    }
}
