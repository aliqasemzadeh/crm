<?php

namespace App\Support;

/**
 * Persian → informal Latin (Finglish) for display fields (e.g. VoIP caller name).
 *
 * Note: ammont/finglify ships a broken words.json where almost all replacements are empty strings,
 * which wipes arbitrary Persian text via strtr — do not use that translate() for names.
 */
final class PersianFinglishConverter
{
    /** @var array<string, string>|null */
    private static ?array $lexicon = null;

    public function convert(string $text): string
    {
        $text = $this->normalize($text);
        if ($text === '') {
            return '';
        }

        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return '';
        }

        $out = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            if (preg_match('/^\s+$/u', $token) === 1) {
                $out[] = $token;

                continue;
            }
            $out[] = $this->convertToken($token);
        }

        return implode('', $out);
    }

    /**
     * NFC, unify Persian/Arabic letters, strip joiners, Persian digits → ASCII.
     */
    public function normalize(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if (is_string($n)) {
                $text = $n;
            }
        }

        $text = str_replace(
            ["\u{0640}", "\u{200C}", "\u{200D}", "\u{FEFF}"],
            '',
            $text
        );

        static $digitMap = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $text = strtr($text, $digitMap);

        static $letterMap = [
            'ي' => 'ی', 'ى' => 'ی', 'ئ' => 'ی', 'ؤ' => 'و', 'أ' => 'ا', 'إ' => 'ا', 'ٱ' => 'ا',
            'ك' => 'ک', 'ة' => 'ه',
            'ﻞ' => 'ل', 'ﻻ' => 'لا',
            'لا' => 'لا',
        ];
        $text = strtr($text, $letterMap);

        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    private function convertToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (preg_match('/^[\p{Latin}0-9.\-_\'’]+$/u', $token) === 1) {
            return strtolower($token);
        }

        $lookup = $this->normalizedLexicon();
        $key = $this->normalize($token);
        if (isset($lookup[$key])) {
            return $lookup[$key];
        }

        return $this->transliterateWord($key);
    }

    /**
     * @return array<string, string>
     */
    private function normalizedLexicon(): array
    {
        if (self::$lexicon !== null) {
            return self::$lexicon;
        }

        $raw = self::lexiconEntries();
        self::$lexicon = [];

        foreach ($raw as $fa => $en) {
            $k = $this->normalize($fa);
            if ($k !== '' && $en !== '') {
                self::$lexicon[$k] = $en;
            }
        }

        return self::$lexicon;
    }

    private function transliterateWord(string $word): string
    {
        $chars = mb_str_split($word);
        $n = count($chars);
        $buf = '';

        for ($i = 0; $i < $n; $i++) {
            $c = $chars[$i];
            $isLast = ($i === $n - 1);

            if ($c === '‌') {
                continue;
            }

            if ($c === 'ع') {
                $buf .= $i === 0 ? '' : 'a';

                continue;
            }

            if ($c === 'ی') {
                $buf .= $isLast ? 'i' : 'y';

                continue;
            }

            if ($c === 'ه' && $isLast) {
                $buf .= 'eh';

                continue;
            }

            $buf .= self::CONSONANT_VOWEL_MAP[$c] ?? '';
        }

        return $buf;
    }

    /**
     * @return array<string, string>
     */
    private static function lexiconEntries(): array
    {
        return [
            'محمد' => 'mohammad', 'محمود' => 'mahmoud', 'احمد' => 'ahmad', 'حمید' => 'hamid',
            'علی' => 'ali', 'حسین' => 'hossein', 'حسن' => 'hasan', 'رضا' => 'reza',
            'مهدی' => 'mehdi', 'امیر' => 'amir', 'سینا' => 'sina', 'پارسا' => 'parsa',
            'فاطمه' => 'fatemeh', 'فاطیما' => 'fatima', 'زهرا' => 'zahra', 'مریم' => 'maryam',
            'سارا' => 'sara', 'نگین' => 'negin', 'نازنین' => 'nazanin', 'پریسا' => 'parisa',
            'یلدا' => 'yalda', 'مبینا' => 'mobina', 'مهسا' => 'mahsa', 'مهتاب' => 'mahtab',
            'الهام' => 'elham', 'شهرام' => 'shahram', 'بهرام' => 'bahram', 'کامران' => 'kamran',
            'فرهاد' => 'farhad', 'اردشیر' => 'ardeshir', 'بابک' => 'babak', 'کیوان' => 'keyvan',
            'نوید' => 'navid', 'فرزاد' => 'farzad', 'سعید' => 'saeed', 'مجید' => 'majid',
            'حمیدرضا' => 'hamidreza', 'علیرضا' => 'alireza', 'محمدرضا' => 'mohammadreza',
            'امیرحسین' => 'amirhossein', 'ابوالفضل' => 'abolfazl', 'مصطفی' => 'mostafa',
            'مجتبی' => 'mojtaba', 'جعفر' => 'jafar', 'ابراهیم' => 'ebrahim', 'اسماعیل' => 'esmail',
            'داود' => 'davood', 'یوسف' => 'yousef', 'سروش' => 'soroush', 'آرش' => 'arash',
            'آرتین' => 'artin', 'آیدا' => 'aida', 'آمنه' => 'ameneh', 'آرمان' => 'arman',
            'بنیامین' => 'benyamin', 'بهزاد' => 'behzad', 'بهروز' => 'behrooz', 'پویان' => 'pouyan',
            'پیمان' => 'peyman', 'تینا' => 'tina', 'ثریا' => 'soraya', 'جلال' => 'jalal',
            'حامد' => 'hamed', 'خدیجه' => 'khadijeh', 'دانیال' => 'danial', 'رامین' => 'ramin',
            'روزبه' => 'roozbeh', 'زینب' => 'zeinab', 'سامان' => 'saman', 'ساناز' => 'sanaz',
            'سپهر' => 'sepehr', 'شاهین' => 'shahin', 'شایان' => 'shayan', 'شیما' => 'shima',
            'صابر' => 'saber', 'صادق' => 'sadegh', 'طاهر' => 'taher',
            'غلام' => 'gholam', 'غلامرضا' => 'gholamreza', 'فریبا' => 'fariba', 'قاسم' => 'ghasem',
            'کاظم' => 'kazem', 'کریم' => 'karim', 'گلناز' => 'golnaz', 'لیلا' => 'leila',
            'مازیار' => 'maziar', 'مرتضی' => 'morteza', 'میلاد' => 'milad',
            'ناصر' => 'naser', 'نسیم' => 'nasim', 'نگار' => 'negar', 'هادی' => 'hadi',
            'هوشنگ' => 'hooshang', 'وحید' => 'vahid', 'یاسر' => 'yaser', 'یاسمن' => 'yasaman',

            'سید' => 'seyed', 'سیده' => 'seyedeh',

            'شرکت' => 'sherkat', 'موسسه' => 'moasseseh', 'فروشگاه' => 'forushgah',
            'کارگاه' => 'kargah', 'کارخانه' => 'karkhaneh', 'اداره' => 'edareh',
            'بازرگانی' => 'bazargani', 'تولیدی' => 'tolidi', 'خدمات' => 'khadamat',
            'صنعت' => 'sanat', 'پخش' => 'pakhsh', 'واردات' => 'vardat', 'صادرات' => 'saderat',

            'آقا' => 'agha', 'خانم' => 'khanom', 'مهندس' => 'mohandes', 'دکتر' => 'doctor',

            'احمدی' => 'ahmadi', 'محمدی' => 'mohammadi', 'رضایی' => 'rezaei', 'علوی' => 'alavi',
            'کریمی' => 'karimi', 'موسوی' => 'mousavi', 'حسینی' => 'hosseini', 'نوری' => 'nouri',
            'کاظمی' => 'kazemi', 'رحیمی' => 'rahimi', 'زارعی' => 'zarei', 'عباسی' => 'abbasi',
            'اکبری' => 'akbari', 'مرادی' => 'moradi', 'فرهادی' => 'farhadi', 'سلطانی' => 'soltani',
            'امینی' => 'amini', 'یزدی' => 'yazdi', 'شیرازی' => 'shirazi', 'تهرانی' => 'tehrani',
            'کوهستانی' => 'koohistani', 'صادقی' => 'sadeghi', 'امیری' => 'amiri', 'محسنی' => 'mohseni',

            'فولاد' => 'foolad', 'پتروشیمی' => 'petroshimi',
        ];
    }

    /** @var array<string, string> */
    private const CONSONANT_VOWEL_MAP = [
        'آ' => 'aa',
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
        'غ' => 'gh',
        'ف' => 'f',
        'ق' => 'gh',
        'ک' => 'k',
        'گ' => 'g',
        'ل' => 'l',
        'م' => 'm',
        'ن' => 'n',
        'و' => 'v',
        'ه' => 'h',
        'ء' => '',
        'ٔ' => '',
        '‌' => '',
    ];
}
