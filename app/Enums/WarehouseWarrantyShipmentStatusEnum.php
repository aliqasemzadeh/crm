<?php

namespace App\Enums;

enum WarehouseWarrantyShipmentStatusEnum: string
{
    case Sent = 'sent';
    case ReceivedAtCompany = 'received_at_company';
    case FollowUp = 'follow_up';
    case SentToWarehouse = 'sent_to_warehouse';
    case Received = 'received';

    public function translationKey(): string
    {
        return match ($this) {
            self::Sent => 'warehouse_warranty_status_sent',
            self::ReceivedAtCompany => 'warehouse_warranty_status_received_at_company',
            self::FollowUp => 'warehouse_warranty_status_follow_up',
            self::SentToWarehouse => 'warehouse_warranty_status_sent_to_warehouse',
            self::Received => 'warehouse_warranty_status_received',
        };
    }

    public function label(): string
    {
        return __('app.'.$this->translationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::Sent => 'sky',
            self::ReceivedAtCompany => 'indigo',
            self::FollowUp => 'amber',
            self::SentToWarehouse => 'violet',
            self::Received => 'emerald',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Received;
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
            return self::Sent;
        }

        return self::tryFrom($value) ?? self::Sent;
    }
}
