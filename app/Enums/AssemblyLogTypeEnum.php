<?php

namespace App\Enums;

enum AssemblyLogTypeEnum: string
{
    case StatusChange = 'status_change';
    case Note = 'note';
    case WarrantyReturn = 'warranty_return';
    case PartSerial = 'part_serial';
    case Assign = 'assign';

    public function translationKey(): string
    {
        return match ($this) {
            self::StatusChange => 'assembly_log_type_status_change',
            self::Note => 'assembly_log_type_note',
            self::WarrantyReturn => 'assembly_log_type_warranty_return',
            self::PartSerial => 'assembly_log_type_part_serial',
            self::Assign => 'assembly_log_type_assign',
        };
    }

    public function label(): string
    {
        return __('app.'.$this->translationKey());
    }

    public function color(): string
    {
        return match ($this) {
            self::StatusChange => 'sky',
            self::Note => 'zinc',
            self::WarrantyReturn => 'indigo',
            self::PartSerial => 'teal',
            self::Assign => 'violet',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
            ],
            self::cases()
        );
    }
}
