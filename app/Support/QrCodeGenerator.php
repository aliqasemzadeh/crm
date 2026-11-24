<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeGenerator
{
    public static function pngDataUri(string $data, int $size = 200): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $data,
            size: $size,
            margin: 8,
        ))->build();

        return $result->getDataUri();
    }
}
