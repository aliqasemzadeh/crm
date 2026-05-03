<?php

namespace App\Console\Commands\Voip;

use Ammont\Finglify\Finglify;
use App\Models\Sepidar\GNR\PartyPhone;
use App\Models\Voip\Phone;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportPhoneFromSepidarCommand extends Command
{
    protected $signature = 'app:voip:import-phones-from-sepidar';

    protected $description = 'Import PartyPhone records from Sepidar (SQL Server) into MySQL phones with normalized numbers and Finglish names.';

    public function finglish_word_dict(): array
    {
        static $dict = null;
        if ($dict !== null) {
            return $dict;
        }
    
        // اینجا دیکشنری رو قرار بده (از JSON بزرگت فقط اون‌هایی که مقدار دارند)
        $dict = [
    
            // پرکاربردها
            "من"   => "man",
            "تو"   => "to",
            "او"   => "ou",
            "ما"   => "ma",
            "شما" => "shoma",
            "آنها" => "anha",
            "اونا" => "oona",
            "این"  => "in",
            "اين"  => "in",
            "اون"  => "oon",
            "اینکه" => "inke",
            "اينکه" => "inke",
            "اینجا" => "inja",
            "اینه" => "ine",
            "اینجام" => "injam",
            "اونجا" => "oonja",
    
            "سلام" => "salam",
            "خداحافظ" => "khodahafez",
            "خداحافظی" => "khodahafezi",
            "بله" => "bale",
            "نه"  => "na",
            "آره" => "are",
            "حتما" => "hatman",
            "لطفا" => "lotfan",
            "ممنون" => "mamnoon",
            "متشکرم" => "moteshakeram",
    
            "از"   => "az",
            "به"   => "be",
            "با"   => "ba",
            "برای" => "baraye",
            "براي" => "baraye",
            "واسه" => "vase",
            "تا"   => "ta",
            "در"   => "dar",
            "روی"  => "rooye",
            "رویِ" => "rooye",
    
            "که"   => "ke",
            "كه"   => "ke",
            "و"    => "va",
            "یا"   => "ya",
            "يا"   => "ya",
            "اما"  => "amma",
            "ولی"  => "vali",
            "ولي"  => "vali",
    
            "خوب"  => "khoob",
            "خوبه" => "khoobe",
            "خوبه؟" => "khoobe?",
            "خیلی" => "kheili",
            "خيلي" => "kheili",
            "زیاد" => "ziad",
            "زیادی" => "ziadi",
    
            "می"   => "mi",
            "مي"   => "mi",
            "نمی"  => "nemi",
            "نمي"  => "nemi",
            "نمیدونم" => "nemidoonam",
            "نميدونم" => "nemidoonam",
    
            "دارم" => "daram",
            "داری" => "dari",
            "داري" => "dari",
            "داریم" => "darim",
            "داريم" => "darim",
            "دارن" => "daran",
    
            "بود"  => "bood",
            "بودم" => "boodam",
            "بودی" => "boodi",
            "بودیم" => "boodim",
            "هست"  => "hast",
            "هستم" => "hastam",
            "هستی" => "hasti",
            "نیست" => "nist",
            "نيست" => "nist",
    
            "میخوام" => "mikham",
            "ميخوام" => "mikham",
            "میخوام" => "mikham",
            "میخواهم" => "mikhaham",
            "میخواد" => "mikhad",
            "میخوای" => "mikhay",
            "ميخواي" => "mikhay",
    
            "دوست" => "doost",
            "دوستت" => "doosetat",
            "دوستت دارم" => "dooset daram",
    
            "زندگی" => "zendegi",
            "زندگي" => "zendegi",
            "خونه" => "khoone",
            "خانه" => "khane",
            "مدرسه" => "madrese",
            "دانشگاه" => "daneshgah",
    
            "کار"  => "kar",
            "کارم" => "karm",
            "کاری" => "kari",
            "کارهات" => "karhat",
            "کارهام" => "karham",
    
            "چرا"  => "chera",
            "چی"   => "chi",
            "چی؟"  => "chi?",
            "چي"   => "chi",
            "چطور" => "chetor",
            "چطوری" => "chetori",
            "چطوری؟" => "chetori?",
            "چیزی" => "chizi",
            "چیز"  => "chiz",
    
            "الان" => "alan",
            "امروز" => "emrooz",
            "دیروز" => "dirooz",
            "فردا" => "farda",
            "امشب" => "emshab",
            "امروز" => "emrooz",
            "دیگه" => "dige",
            "ديگه" => "dige",
    
            // چند فعل پرکاربرد
            "میام" => "miam",
            "میری" => "miri",
            "میرم" => "miram",
            "میره" => "mire",
            "رفتم" => "raftam",
            "رفتی" => "rafti",
            "رفته" => "rafte",
    
            "دیدم" => "didam",
            "دیدی" => "didi",
            "دید"  => "did",
    
            "گفتم" => "goftam",
            "گفتی" => "gofti",
            "گفت"  => "goft",
    
            "شنیدم" => "shenidam",
            "شنیدی" => "shenidi",
    
            // چند نمونه از لیست بزرگ تو (نمونه؛ بقیه را خودت اضافه کن)
            "ببین" => "bebin",
            "ببين" => "bebin",
            "برو"  => "boro",
            "بیا"  => "bia",
            "بيا"  => "bia",
            "بیاور" => "biavar",
            "بیاورید" => "biavarid",
    
            "ندارم" => "nadaram",
            "نداري" => "nadari",
            "نداریم" => "nadarim",
    
            "میدونم" => "midoonam",
            "ميدونم" => "midunam",
            "میدونی" => "midooni",
            "ميدوني" => "miduni",
    
            // می‌تونی بقیه‌ی مپ عظیمت رو همین‌جا پیست کنی
            // "..." => "...",
        ];
    
        return $dict;
    }
    
    
    /**
     * تبدیل یک متن فارسی به فینگلیش
     * ۱) تلاش با دیکشنری کلمه‌به‌کلمه
     * ۲) اگر نبود، تبدیل حرف‌به‌حرف
     */
    public function persian_to_finglish(string $text): string
    {
        $dict = $this->finglish_word_dict();
    
        // مپ چندحرفی برای بعضی ترکیبات
        static $multiCharMap = [
            'بی'  => 'bi',
            'جم'  => 'jam',
            'مج'  => 'maj',
            'اند' => 'and',
            'ان'  => 'an',
            'عی'  => 'ae',
            'ای'  => 'i',
            'او'  => 'oo',
        ];
    
        // مپ تک‌حرفی
        static $singleCharMap = [
            'آ' => 'a',
            'ا' => 'a',
            'ب' => 'b',
            'پ' => 'p',
            'ت' => 't',
            'ث' => 's',
            'ج' => 'j',
            'چ' => 'ch',
            'ح' => 'h',
            'خ' => 'kh',
            'د' => 'd',
            'ذ' => 'z',
            'ر' => 'r',
            'ز' => 'z',
            'ژ' => 'zh',
            'س' => 's',
            'ش' => 'sh',
            'ص' => 's',
            'ض' => 'z',
            'ط' => 't',
            'ظ' => 'z',
            'ع' => 'a',   // می‌تونی '' هم بذاری
            'غ' => 'gh',
            'ف' => 'f',
            'ق' => 'gh',  // یا 'q'
            'ک' => 'k',
            'ك' => 'k',
            'گ' => 'g',
            'ل' => 'l',
            'م' => 'm',
            'ن' => 'n',
            'و' => 'o',   // می‌تونی 'v' هم جایی استفاده کنی
            'ه' => 'h',   // آخر کلمه بعداً → eh
            'ی' => 'i',
            'ي' => 'i',
            'ء' => '',
            '‌' => ' ',           // نیم‌فاصله
            "\u{200C}" => ' ',
        ];
    
        $text = trim($text);
        if ($text === '') {
            return '';
        }
    
        // حفظ فاصله‌ها: با delimiter هم برمی‌گردونیم
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = [];
    
        foreach ($tokens as $t) {
            // اگر فقط فاصله است، همون رو اضافه کن
            if (preg_match('/^\s+$/u', $t) || $t === '') {
                $result[] = $t;
                continue;
            }
    
            // ۱) اگر کلمه در دیکشنری بود
            if (isset($dict[$t]) && $dict[$t] !== '') {
                $result[] = $dict[$t];
                continue;
            }
    
            // ۲) اگر نبود، با fallback حرفی
            $tmp = $t;
    
            // مپ چندحرفی
            foreach ($multiCharMap as $fa => $en) {
                $tmp = preg_replace('/' . preg_quote($fa, '/') . '/u', $en, $tmp);
            }
    
            // مپ تک‌حرفی
            $latin = strtr($tmp, $singleCharMap);
    
            // بهبود پایان کلمات با «ه»
            $latin = preg_replace('/ah$/u', 'eh', $latin);
            $latin = preg_replace('/h$/u', 'eh', $latin);
    
            $result[] = $latin;
        }
    
        return implode('', $result);
    }

    

    public function handle(): int
    {
        $finglify = new Finglify;
        $now = Carbon::now();
        $imported = 0;
        $skipped = 0;

        PartyPhone::query()
            ->with(['party' => static fn ($q) => $q->select(['PartyId', 'Name', 'LastName'])])
            ->orderBy('PartyPhoneId')
            ->chunkById(500, function ($partyPhones) use ($finglify, $now, &$imported, &$skipped) {
                $rows = [];

                foreach ($partyPhones as $partyPhone) {
                    $raw = (string) ($partyPhone->Phone ?? '');
                    $normalized = $this->normalizeIranPhoneNumber($raw);

                    if ($normalized === null) {
                        $skipped++;

                        continue;
                    }

                    $name = $this->buildPartyDisplayName($partyPhone->party);
                    $nameLatin = '';
                    if ($name !== '') {
                        $translated = trim($this->persian_to_finglish($name));
                        $spaced = preg_replace('/\s+/u', ' ', $translated);
                        $nameLatin = strtolower(is_string($spaced) ? $spaced : '');
                    }

                    $rows[] = [
                        'number' => $normalized,
                        'name' => $name,
                        'name_latin' => $nameLatin,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $rows = $this->uniqueRowsByNumber($rows);

                if ($rows !== []) {
                    Phone::query()->upsert(
                        $rows,
                        ['number'],
                        ['name', 'name_latin', 'updated_at']
                    );
                    $imported += count($rows);
                }
            }, 'PartyPhoneId');

        $this->info("Upserted {$imported} phone row(s); skipped {$skipped} invalid or empty number(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{number: string, name: string, name_latin: string, created_at: Carbon, updated_at: Carbon}>  $rows
     * @return array<int, array{number: string, name: string, name_latin: string, created_at: Carbon, updated_at: Carbon}>
     */
    private function uniqueRowsByNumber(array $rows): array
    {
        $byNumber = [];

        foreach ($rows as $row) {
            $byNumber[$row['number']] = $row;
        }

        return array_values($byNumber);
    }

    private function buildPartyDisplayName(?object $party): string
    {
        if ($party === null) {
            return '';
        }

        $parts = array_filter([
            isset($party->Name) ? trim((string) $party->Name) : '',
            isset($party->LastName) ? trim((string) $party->LastName) : '',
        ], static fn (string $p): bool => $p !== '');

        return trim(implode(' ', $parts));
    }

    private function normalizeIranPhoneNumber(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '' || strlen($digits) < 5) {
            return null;
        }

        if (str_starts_with($digits, '09')) {
            return substr($digits, 1);
        }

        if (str_starts_with($digits, '071')) {
            return substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }
}
