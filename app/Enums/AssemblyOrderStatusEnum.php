<?php

namespace App\Enums;

enum AssemblyOrderStatusEnum: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case PartiallyReady = 'partially_ready';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function translationKey(): string
    {
        return match ($this) {
            self::Pending => 'assembly_order_status_pending',
            self::InProgress => 'assembly_order_status_in_progress',
            self::PartiallyReady => 'assembly_order_status_partially_ready',
            self::Ready => 'assembly_order_status_ready',
            self::Delivered => 'assembly_order_status_delivered',
            self::Cancelled => 'assembly_order_status_cancelled',
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
            self::PartiallyReady => 'orange',
            self::Ready => 'green',
            self::Delivered => 'emerald',
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
