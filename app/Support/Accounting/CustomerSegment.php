<?php

namespace App\Support\Accounting;

final class CustomerSegment
{
    public const ALL = 'all';

    public const GOVERNMENT = 'government';

    public const PRIVATE = 'private';

    public const COLLEAGUE = 'colleague';

    public const VALUABLE = 'valuable';

    /**
     * @return array<string, list<int>>
     */
    public static function groupingIds(): array
    {
        return [
            self::GOVERNMENT => [51, 52, 53, 612],
            self::PRIVATE => [54],
            self::COLLEAGUE => [58, 63],
        ];
    }

    /**
     * @return list<int>|null
     */
    public static function groupingIdsFor(string $segment): ?array
    {
        return self::groupingIds()[$segment] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::ALL => __('app.customer_segment_all'),
            self::GOVERNMENT => __('app.customer_segment_government'),
            self::PRIVATE => __('app.customer_segment_private'),
            self::COLLEAGUE => __('app.customer_segment_colleague'),
            self::VALUABLE => __('app.customer_segment_valuable'),
        ];
    }

    public static function isValuable(string $segment): bool
    {
        return $segment === self::VALUABLE;
    }
}
