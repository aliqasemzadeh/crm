<?php

namespace App\Support;

final class PersianAmountFormatter
{
    /**
     * Compact Persian amount for SMS, e.g. "200 هزار تومان" or "یک میلیون تومان".
     */
    public static function formatCharacter(int $amount): string
    {
        $toman = __('app.toman');

        if ($amount <= 0) {
            return number_format($amount).' '.$toman;
        }

        if ($amount % 1_000 !== 0) {
            return number_format($amount).' '.$toman;
        }

        $millions = intdiv($amount, 1_000_000);
        $remainder = $amount % 1_000_000;
        $parts = [];

        if ($millions > 0) {
            $parts[] = self::numberToWords($millions).' میلیون';
        }

        if ($remainder >= 1_000) {
            $parts[] = intdiv($remainder, 1_000).' هزار';
        }

        if ($parts === []) {
            return number_format($amount).' '.$toman;
        }

        return implode(' و ', $parts).' '.$toman;
    }

    private static function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        $ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
        $teens = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
        $tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
        $hundreds = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
        $scales = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];

        $parts = [];
        $scaleIndex = 0;
        $remaining = abs($number);

        while ($remaining > 0 && $scaleIndex < count($scales)) {
            $chunk = $remaining % 1000;

            if ($chunk > 0) {
                $chunkWords = self::threeDigitsToWords($chunk, $ones, $teens, $tens, $hundreds);
                $scale = $scales[$scaleIndex];
                $parts[] = $scale !== '' ? $chunkWords.' '.$scale : $chunkWords;
            }

            $remaining = intdiv($remaining, 1000);
            $scaleIndex++;
        }

        return implode(' و ', array_reverse($parts));
    }

    /**
     * @param  list<string>  $ones
     * @param  list<string>  $teens
     * @param  list<string>  $tens
     * @param  list<string>  $hundreds
     */
    private static function threeDigitsToWords(int $n, array $ones, array $teens, array $tens, array $hundreds): string
    {
        if ($n === 0) {
            return '';
        }

        $parts = [];
        $h = intdiv($n, 100);
        $rem = $n % 100;

        if ($h > 0) {
            $parts[] = $hundreds[$h];
        }

        if ($rem >= 10 && $rem <= 19) {
            $parts[] = $teens[$rem - 10];
        } else {
            $t = intdiv($rem, 10);
            $o = $rem % 10;

            if ($t > 0) {
                $parts[] = $tens[$t];
            }

            if ($o > 0) {
                $parts[] = $ones[$o];
            }
        }

        return implode(' و ', $parts);
    }
}
