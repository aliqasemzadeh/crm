<?php

namespace App\Enums;

enum StatusEnum: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case SentToWarranty = 'sent_to_warranty';
    case CostRegistered = 'cost_registered';
    case Completed = 'completed';
    case UnderReview = 'under_review';
    case Delivered = 'delivered';
    case Rejected = 'rejected';
    case UnderRepair = 'under_repair';

    /**
     * Get the translation key for the status.
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::New => 'repair_status_new',
            self::InProgress => 'repair_status_in_progress',
            self::SentToWarranty => 'repair_status_sent_to_warranty',
            self::CostRegistered => 'repair_status_cost_registered',
            self::Completed => 'repair_status_completed',
            self::UnderReview => 'repair_status_under_review',
            self::Delivered => 'repair_status_delivered',
            self::Rejected => 'repair_status_rejected',
            self::UnderRepair => 'repair_status_under_repair',
        };
    }

    /**
     * Get the translated label for the status.
     */
    public function label(): string
    {
        return __('app.'.$this->translationKey());
    }

    /**
     * Get the color for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::InProgress => 'amber',
            self::SentToWarranty => 'indigo',
            self::CostRegistered => 'purple',
            self::Completed => 'green',
            self::UnderReview => 'yellow',
            self::Delivered => 'emerald',
            self::Rejected => 'red',
            self::UnderRepair => 'orange',
        };
    }

    /**
     * Get all status cases as an array with value, label, and color.
     *
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

    /**
     * Safely get a status enum from a value, returning New as default if not found.
     *
     * @param string|null $value
     * @return self
     */
    public static function tryFromSafe(?string $value): self
    {
        if ($value === null) {
            return self::New;
        }

        return self::tryFrom($value) ?? self::New;
    }
}
