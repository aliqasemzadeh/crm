<?php

namespace App\Livewire\Panels\Crm\Dashboard;

use App\Models\Issabel\Cdr;
use App\Models\Issabel\Device;
use App\Models\User;
use App\Models\Voip\Phone;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Throwable;

class Index extends Component
{
    public array $calls = [];

    public bool $hasMore = true;

    public ?string $cursorCalldate = null;

    public ?string $cursorUniqueid = null;

    public bool $loadingMore = false;

    public ?string $loadError = null;

    private const PAGE_SIZE = 50;

    private const PHONES_CACHE_KEY = 'crm.voip_phones_number_to_name';

    private const DEVICES_CACHE_KEY = 'crm.issabel_devices_user_to_description';

    private const INTERNAL_TWO_DIGIT_USER_PROFILES_CACHE_KEY = 'crm.users_by_internal_two_digit_extension';

    private const PHONES_CACHE_TTL_SECONDS = 600;

    public function mount(): void
    {
        $this->fetchBatch(true);
    }

    public function loadMore(): void
    {
        $this->fetchBatch(false);
    }

    private function fetchBatch(bool $initial): void
    {
        if ($this->loadingMore) {
            return;
        }

        if (! $initial && ! $this->hasMore) {
            return;
        }

        $this->loadingMore = true;

        try {
            $query = Cdr::query()
                ->validCalldate()
                ->excludeInternalTwoDigitExtensions();

            if (! $initial && $this->cursorCalldate !== null && $this->cursorUniqueid !== null) {
                $cursorDate = $this->cursorCalldate;
                $cursorId = $this->cursorUniqueid;
                $query->where(function ($q) use ($cursorDate, $cursorId) {
                    $q->where('calldate', '<', $cursorDate)
                        ->orWhere(function ($q2) use ($cursorDate, $cursorId) {
                            $q2->where('calldate', '=', $cursorDate)
                                ->where('uniqueid', '<', $cursorId);
                        });
                });
            }

            $rows = $query
                ->orderByDesc('calldate')
                ->orderByDesc('uniqueid')
                ->limit(self::PAGE_SIZE)
                ->get();

            if ($rows->isEmpty()) {
                $this->hasMore = false;
                $this->loadingMore = false;

                return;
            }

            $phoneMap = $this->phoneNumberToNameMap();
            $deviceMap = $this->deviceUserToDescriptionMap();
            $internalProfiles = $this->internalTwoDigitUserProfiles();
            $mapped = [];

            foreach ($rows as $row) {
                $mapped[] = $this->mapRow($row, $phoneMap, $deviceMap, $internalProfiles);
            }

            $this->calls = array_merge($this->calls, $mapped);

            $last = $rows->last();
            $this->cursorCalldate = $last->calldate->format('Y-m-d H:i:s');
            $this->cursorUniqueid = $last->uniqueid;

            $this->hasMore = $rows->count() === self::PAGE_SIZE;
        } catch (Throwable $e) {
            report($e);
            $this->loadError = __('app.call_history_load_failed');
            $this->hasMore = false;
        } finally {
            $this->loadingMore = false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function phoneNumberToNameMap(): array
    {
        return Cache::remember(
            self::PHONES_CACHE_KEY,
            self::PHONES_CACHE_TTL_SECONDS,
            fn (): array => Phone::query()->pluck('name', 'number')->all()
        );
    }

    /**
     * نقشهٔ user → description از جدول devices (تماس‌های داخلی).
     *
     * @return array<string, string>
     */
    private function deviceUserToDescriptionMap(): array
    {
        return Cache::remember(
            self::DEVICES_CACHE_KEY,
            self::PHONES_CACHE_TTL_SECONDS,
            function (): array {
                $map = [];
                foreach (Device::query()->get(['user', 'dial', 'description']) as $device) {
                    $desc = trim((string) $device->description);
                    if ($desc === '') {
                        continue;
                    }

                    foreach ([(string) $device->user, (string) $device->dial] as $col) {
                        $t = trim($col);
                        if ($t === '') {
                            continue;
                        }
                        $map[$t] = $desc;
                        $digits = preg_replace('/\D+/', '', $t) ?? '';
                        if ($digits !== '' && strlen($digits) <= 5) {
                            $map[$digits] = $desc;
                            $stripped = ltrim($digits, '0');
                            if ($stripped !== '' && $stripped !== $digits) {
                                $map[$stripped] = $desc;
                            }
                        }
                    }
                }

                return $map;
            }
        );
    }

    /**
     * پروفایل کاربر CRM برای داخلی‌های دقیقاً دو رقمی (users.internal_phone_id → devices.id).
     *
     * @return array<string, array{name: string, avatar_url: ?string, has_avatar: bool}>
     */
    private function internalTwoDigitUserProfiles(): array
    {
        return Cache::remember(
            self::INTERNAL_TWO_DIGIT_USER_PROFILES_CACHE_KEY,
            self::PHONES_CACHE_TTL_SECONDS,
            function (): array {
                $map = [];
                $users = User::query()
                    ->whereNotNull('internal_phone_id')
                    ->with('internalPhoneDevice')
                    ->get();

                foreach ($users as $user) {
                    $device = $user->internalPhoneDevice;
                    if ($device === null) {
                        continue;
                    }

                    foreach ($this->twoDigitKeysFromDevice($device) as $key) {
                        $map[$key] = [
                            'name' => $user->name,
                            'avatar_url' => $user->getAvatarUrl(),
                            'has_avatar' => (bool) $user->avatar,
                        ];
                    }
                }

                return $map;
            }
        );
    }

    /**
     * @return list<string>
     */
    private function twoDigitKeysFromDevice(Device $device): array
    {
        $keys = [];
        foreach ([trim((string) $device->user), trim((string) $device->dial)] as $t) {
            if ($t === '') {
                continue;
            }
            if (preg_match('/^\d{2}$/', $t) === 1) {
                $keys[] = $t;
            }
            $digits = preg_replace('/\D+/', '', $t) ?? '';
            if (preg_match('/^\d{2}$/', $digits) === 1) {
                $keys[] = $digits;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * استخراج شمارهٔ داخلی دو رقمی از فیلدهای خام CDR برای تطبیق با کاربران CRM.
     */
    private function extractTwoDigitExtension(string $raw): ?string
    {
        $trim = trim($raw);
        if ($trim === '') {
            return null;
        }

        $normalized = $this->normalizeExtensionCandidate($trim);
        $digitsOnly = preg_replace('/\D+/', '', $trim) ?? '';

        foreach ([$normalized, $trim, $digitsOnly] as $candidate) {
            if ($candidate !== '' && preg_match('/^\d{2}$/', $candidate) === 1) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * دادهٔ نمایش طرف تماس برای UI (آواتار مثل صفحهٔ کاربران ادمین، فقط برای داخلی دو رقمی آواتار کوچک در مسیر).
     *
     * @param  array<string, array{name: string, avatar_url: ?string, has_avatar: bool}>  $internalProfiles
     * @return array{display: string, avatar_url: ?string, has_avatar: bool, avatar_name: string, is_two_digit_party: bool}
     */
    private function partyPresentation(string $raw, string $displayLabel, array $internalProfiles): array
    {
        $ext = $this->extractTwoDigitExtension($raw);
        $profile = ($ext !== null && isset($internalProfiles[$ext])) ? $internalProfiles[$ext] : null;

        $display = $profile !== null ? $profile['name'] : $displayLabel;
        $avatarName = $profile !== null ? $profile['name'] : $displayLabel;

        return [
            'display' => $display,
            'avatar_url' => $profile['avatar_url'] ?? null,
            'has_avatar' => $profile !== null && $profile['has_avatar'],
            'avatar_name' => $avatarName !== '' ? $avatarName : $displayLabel,
            'is_two_digit_party' => $ext !== null,
        ];
    }

    /**
     * @param  array<string, string>  $phoneMap
     * @param  array<string, string>  $deviceMap
     * @param  array<string, array{name: string, avatar_url: ?string, has_avatar: bool}>  $internalProfiles
     * @return array<string, mixed>
     */
    private function mapRow(Cdr $row, array $phoneMap, array $deviceMap, array $internalProfiles): array
    {
        $cnum = trim((string) $row->cnum);
        $appearance = $this->dispositionAppearance((string) $row->disposition);
        $billsec = (int) $row->billsec;

        // جهت نمایش مسیر: مقصد CDR سمت چپ، مبدأ سمت راست (مثلاً 32319051 → نام داخلی)
        $routeFromLabel = $this->resolvePartyLabel((string) $row->dst, $deviceMap, $phoneMap);
        $routeToLabel = $this->resolvePartyLabel((string) $row->src, $deviceMap, $phoneMap);

        // عنوان کارت: نام واقعی (devices / CRM)؛ اگر هنوز فقط شمارهٔ کوتاه بود از طرف‌های مسیر کمک بگیر
        $primaryPartyRaw = $cnum !== '' ? $cnum : (string) $row->src;
        $phoneDisplay = $this->resolveHeadingDisplay(
            $primaryPartyRaw,
            (string) $row->src,
            (string) $row->dst,
            $deviceMap,
            $phoneMap,
            $routeToLabel,
            $routeFromLabel
        );

        $headingRaw = $cnum !== '' ? $cnum : (string) $row->src;
        $headingParty = $this->partyPresentation($headingRaw, $phoneDisplay, $internalProfiles);

        return [
            'uniqueid' => $row->uniqueid,
            'jalali_datetime' => Jalalian::fromDateTime($row->calldate)->format('Y/m/d H:i'),
            'disposition_raw' => $row->disposition,
            'disposition_label' => $appearance['label'],
            'indicator_color' => $appearance['color'],
            'badge_color' => $appearance['color'],
            'phone_display' => $headingParty['display'],
            'heading_party' => $headingParty,
            'route_from_party' => $this->partyPresentation((string) $row->dst, $routeFromLabel, $internalProfiles),
            'route_to_party' => $this->partyPresentation((string) $row->src, $routeToLabel, $internalProfiles),
            'duration_display' => $this->formatBillsec($billsec),
            'billsec' => $billsec,
        ];
    }

    /**
     * نام نمایشی یک طرف تماس: اول devices (داخلی)، بعد مخاطبین CRM، در نهایت خود رشتهٔ خام.
     *
     * @param  array<string, string>  $deviceMap
     * @param  array<string, string>  $phoneMap
     */
    private function resolvePartyLabel(string $raw, array $deviceMap, array $phoneMap): string
    {
        $trim = trim($raw);
        if ($trim === '') {
            return __('app.unknown_call_party');
        }

        foreach ($this->partyLookupKeys($trim) as $key) {
            if (isset($deviceMap[$key])) {
                return $deviceMap[$key];
            }
        }

        foreach ($this->partyLookupKeys($trim) as $key) {
            if (isset($phoneMap[$key])) {
                return $phoneMap[$key];
            }
        }

        $readable = $this->normalizeExtensionCandidate($trim);
        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        return $readable !== '' ? $readable : ($digits !== '' ? $digits : $trim);
    }

    /**
     * کلیدهای ممکن برای تطبیق با devices.user و phones.number (صفرهای پیشرو، کانال SIP، …).
     *
     * @return list<string>
     */
    private function partyLookupKeys(string $trim): array
    {
        $normalized = $this->normalizeExtensionCandidate($trim);
        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        $keys = [];
        foreach ([$trim, $normalized, $digits] as $key) {
            if ($key !== '' && ! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        if ($digits !== '' && strlen($digits) <= 5) {
            $stripped = ltrim($digits, '0');
            if ($stripped !== '' && $stripped !== $digits && ! in_array($stripped, $keys, true)) {
                $keys[] = $stripped;
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, string>  $deviceMap
     * @param  array<string, string>  $phoneMap
     */
    private function resolveHeadingDisplay(
        string $primaryRaw,
        string $srcRaw,
        string $dstRaw,
        array $deviceMap,
        array $phoneMap,
        string $routeToLabel,
        string $routeFromLabel,
    ): string {
        $primary = trim($primaryRaw);
        $baseRaw = $primary !== '' ? $primary : $srcRaw;
        $label = $this->resolvePartyLabel($baseRaw, $deviceMap, $phoneMap);

        if ($this->isShortNumericExtensionLabel($label)) {
            foreach ([
                $this->resolvePartyLabel($srcRaw, $deviceMap, $phoneMap),
                $routeToLabel,
                $routeFromLabel,
                $this->resolvePartyLabel($dstRaw, $deviceMap, $phoneMap),
            ] as $alt) {
                if ($alt !== '' && $alt !== __('app.unknown_call_party') && ! $this->isShortNumericExtensionLabel($alt)) {
                    return $alt;
                }
            }
        }

        return $label;
    }

    private function isShortNumericExtensionLabel(string $label): bool
    {
        return $label !== '' && preg_match('/^\d{1,6}$/', $label) === 1;
    }

    /**
     * استخراج شمارهٔ داخلی از مقادیر канала Asterisk برای تطبیق با devices.user.
     */
    private function normalizeExtensionCandidate(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $raw)) {
            return $raw;
        }

        if (preg_match('#^(?:SIP|PJSIP|IAX2|DAHDI)/([^/@\-]+)#i', $raw, $m)) {
            $part = $m[1];
            if (preg_match('/^(\d+)/', $part, $m2)) {
                return $m2[1];
            }

            return $part;
        }

        if (preg_match('#^Local/(\d+)@#i', $raw, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * @return array{color: string, label: string}
     */
    private function dispositionAppearance(string $disposition): array
    {
        $key = strtoupper(trim($disposition));

        $map = [
            'ANSWERED' => ['color' => 'green', 'label_key' => 'app.cdr_disposition_answered'],
            'NO ANSWER' => ['color' => 'red', 'label_key' => 'app.cdr_disposition_no_answer'],
            'BUSY' => ['color' => 'amber', 'label_key' => 'app.cdr_disposition_busy'],
            'FAILED' => ['color' => 'rose', 'label_key' => 'app.cdr_disposition_failed'],
            'CONGESTION' => ['color' => 'orange', 'label_key' => 'app.cdr_disposition_congestion'],
        ];

        if (! isset($map[$key])) {
            return [
                'color' => 'zinc',
                'label' => $disposition !== '' ? $disposition : __('app.cdr_disposition_unknown'),
            ];
        }

        $entry = $map[$key];

        return [
            'color' => $entry['color'],
            'label' => __($entry['label_key']),
        ];
    }

    private function formatBillsec(int $billsec): string
    {
        if ($billsec <= 0) {
            return '—';
        }

        $m = intdiv($billsec, 60);
        $s = $billsec % 60;

        return sprintf('%d:%02d', $m, $s);
    }

    #[Layout('layouts.panels.crm')]
    public function render()
    {
        return view('livewire.panels.crm.dashboard.index');
    }
}
