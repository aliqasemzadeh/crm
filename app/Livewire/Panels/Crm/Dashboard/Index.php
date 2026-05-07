<?php

namespace App\Livewire\Panels\Crm\Dashboard;

use App\Models\Issabel\Cdr;
use App\Models\Issabel\Device;
use App\Models\User;
use App\Models\Voip\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
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

    public ?int $userFilter = null;

    public ?string $directionFilter = null;

    /**
     * When scoped to a specific user, these are the extension keys used for:
     * - visibility query scope
     * - deriving incoming/outgoing direction in the UI
     *
     * @var list<string>
     */
    private array $scopedExtensionKeys = [];

    private const PAGE_SIZE = 50;

    private const PHONES_CACHE_KEY = 'crm.voip_phones_number_to_name';

    private const DEVICES_CACHE_KEY = 'crm.issabel_devices_user_to_description';

    private const INTERNAL_USER_PROFILES_BY_EXTENSION_CACHE_KEY = 'crm.users_by_internal_phone_extension_v2';

    private const PHONES_CACHE_TTL_SECONDS = 600;

    public function mount(): void
    {
        $this->fetchBatch(true);
    }

    public function loadMore(): void
    {
        $this->fetchBatch(false);
    }

    public function updatedUserFilter(): void
    {
        if (! $this->isAdministrator) {
            $this->userFilter = null;

            return;
        }

        $this->resetTimeline();
        $this->fetchBatch(true);
    }

    public function updatedDirectionFilter(): void
    {
        $allowed = [null, '', 'incoming', 'outgoing', 'internal'];
        if (! in_array($this->directionFilter, $allowed, true)) {
            $this->directionFilter = null;
        }

        $this->resetTimeline();
        $this->fetchBatch(true);
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
            // Reset for this batch; will be set again by applyVisibilityScope/applyUserScope.
            $this->scopedExtensionKeys = [];

            $query = Cdr::query()
                ->validCalldate()
                ->excludeInternalTwoDigitExtensions();

            $this->applyVisibilityScope($query);

            $this->applyDirectionScope($query);

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
            $internalProfiles = $this->internalUserProfilesByExtension();
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

    private function resetTimeline(): void
    {
        $this->calls = [];
        $this->hasMore = true;
        $this->cursorCalldate = null;
        $this->cursorUniqueid = null;
        $this->loadError = null;
    }

    private function applyVisibilityScope(Builder $query): void
    {
        $authUser = auth()->user();
        if (! $authUser instanceof User) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($this->isAdministrator) {
            if ($this->userFilter === null) {
                // Admin viewing "all calls": there is no single user context for call direction.
                $this->scopedExtensionKeys = [];
                return;
            }

            $user = User::query()->find($this->userFilter);
            if (! $user instanceof User) {
                $query->whereRaw('1 = 0');

                return;
            }

            $this->applyUserScope($query, $user);

            return;
        }

        $this->applyUserScope($query, $authUser);
    }

    private function applyDirectionScope(Builder $query): void
    {
        if ($this->directionFilter === null || $this->directionFilter === '' || $this->scopedExtensionKeys === []) {
            return;
        }

        $keys = $this->scopedExtensionKeys;

        $query->where(function (Builder $q) use ($keys) {
            if ($this->directionFilter === 'incoming') {
                $q->whereIn('dst', $keys)
                    ->whereNotIn('src', $keys);

                return;
            }

            if ($this->directionFilter === 'outgoing') {
                $q->whereIn('src', $keys)
                    ->whereNotIn('dst', $keys);

                return;
            }

            if ($this->directionFilter === 'internal') {
                $q->whereIn('src', $keys)
                    ->whereIn('dst', $keys);
            }
        });
    }

    private function applyUserScope(Builder $query, User $user): void
    {
        $user->loadMissing('internalPhoneDevice');
        $device = $user->internalPhoneDevice;
        if ($device === null) {
            $this->scopedExtensionKeys = [];
            $query->whereRaw('1 = 0');

            return;
        }

        $keys = $this->extensionKeysFromDevice($device);
        if ($keys === []) {
            $this->scopedExtensionKeys = [];
            $query->whereRaw('1 = 0');

            return;
        }

        $this->scopedExtensionKeys = $keys;

        $query->where(function (Builder $q) use ($keys) {
            $q->whereIn('src', $keys)
                ->orWhereIn('dst', $keys)
                ->orWhereIn('cnum', $keys);
        });
    }

    #[Computed]
    public function isAdministrator(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('administrator');
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    #[Computed]
    public function usersForFilter()
    {
        if (! $this->isAdministrator) {
            return collect();
        }

        return User::query()
            ->whereNotNull('internal_phone_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'internal_phone_id', 'avatar']);
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
     * پروفایل کاربر CRM برای داخلی متصل به users.internal_phone_id → Issabel devices.
     *
     * @return array<string, array{name: string, avatar_url: ?string}>
     */
    private function internalUserProfilesByExtension(): array
    {
        return Cache::remember(
            self::INTERNAL_USER_PROFILES_BY_EXTENSION_CACHE_KEY,
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

                    $profile = [
                        'name' => $user->name,
                        'avatar_url' => $user->getAvatarUrl(),
                    ];

                    foreach ($this->extensionKeysFromDevice($device) as $key) {
                        $map[$key] = $profile;
                    }
                }

                return $map;
            }
        );
    }

    /**
     * همان کلیدهای قابل جستجو برای devices.user / dial تا با partyLookupKeys در CDR هم‌خوان باشد.
     *
     * @return list<string>
     */
    private function extensionKeysFromDevice(Device $device): array
    {
        $keys = [];
        foreach ([trim((string) $device->user), trim((string) $device->dial)] as $col) {
            if ($col === '') {
                continue;
            }

            $keys[] = $col;
            $digits = preg_replace('/\D+/', '', $col) ?? '';
            if ($digits !== '') {
                $keys[] = $digits;
                if (strlen($digits) <= 5) {
                    $stripped = ltrim($digits, '0');
                    if ($stripped !== '' && $stripped !== $digits) {
                        $keys[] = $stripped;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * دادهٔ نمایش طرف تماس: اگر داخلی به کاربر CRM وصل باشد نام و آواتار همان کاربر؛ در غیر این صورت فقط برچسب (مثلاً مخاطب یا شماره خارجی).
     *
     * @param  array<string, array{name: string, avatar_url: ?string}>  $internalProfiles
     * @return array{display: string, avatar_url: ?string, avatar_name: string, is_internal_user: bool}
     */
    private function partyPresentation(string $raw, string $displayLabel, array $internalProfiles): array
    {
        $profile = null;
        foreach ($this->partyLookupKeys($raw) as $key) {
            if (isset($internalProfiles[$key])) {
                $profile = $internalProfiles[$key];
                break;
            }
        }

        $normalized = $this->normalizeExtensionCandidate($raw);
        if ($profile === null && $normalized !== '' && isset($internalProfiles[$normalized])) {
            $profile = $internalProfiles[$normalized];
        }

        $display = $profile !== null ? $profile['name'] : $displayLabel;
        $avatarName = $profile !== null ? $profile['name'] : $displayLabel;

        return [
            'display' => $display,
            'avatar_url' => $profile['avatar_url'] ?? null,
            'avatar_name' => $avatarName !== '' ? $avatarName : $displayLabel,
            'is_internal_user' => $profile !== null,
        ];
    }

    /**
     * @param  array<string, string>  $phoneMap
     * @param  array<string, string>  $deviceMap
     * @param  array<string, array{name: string, avatar_url: ?string}>  $internalProfiles
     * @return array<string, mixed>
     */
    private function mapRow(Cdr $row, array $phoneMap, array $deviceMap, array $internalProfiles): array
    {
        $cnum = trim((string) $row->cnum);
        $appearance = $this->dispositionAppearance((string) $row->disposition);
        $billsec = (int) $row->billsec;

        $direction = $this->callDirectionForScopedUser($row);

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
        $callerTooltipNumber = $this->callerTooltipNumber($headingRaw, $headingParty['display']);

        $routeFromParty = $this->partyPresentation((string) $row->dst, $routeFromLabel, $internalProfiles);
        $routeFromParty['tooltip_number'] = $this->callerTooltipNumber((string) $row->dst, $routeFromParty['display']);

        $routeToParty = $this->partyPresentation((string) $row->src, $routeToLabel, $internalProfiles);
        $routeToParty['tooltip_number'] = $this->callerTooltipNumber((string) $row->src, $routeToParty['display']);

        return [
            'uniqueid' => $row->uniqueid,
            'jalali_datetime' => Jalalian::fromDateTime($row->calldate)->format('Y/m/d H:i'),
            'disposition_raw' => $row->disposition,
            'disposition_label' => $appearance['label'],
            'indicator_color' => $appearance['color'],
            'badge_color' => $appearance['color'],
            'direction_label' => $direction['label'],
            'direction_color' => $direction['color'],
            'phone_display' => $headingParty['display'],
            'caller_tooltip_number' => $callerTooltipNumber,
            'heading_party' => $headingParty,
            'route_from_party' => $routeFromParty,
            'route_to_party' => $routeToParty,
            'duration_display' => $this->formatBillsec($billsec),
            'billsec' => $billsec,
        ];
    }

    /**
     * Direction is only meaningful when viewing calls scoped to a single user's extension(s).
     *
     * @return array{label: ?string, color: string}
     */
    private function callDirectionForScopedUser(Cdr $row): array
    {
        if ($this->scopedExtensionKeys === []) {
            return ['label' => null, 'color' => 'zinc'];
        }

        $src = trim((string) $row->src);
        $dst = trim((string) $row->dst);

        $isOutgoing = $src !== '' && in_array($src, $this->scopedExtensionKeys, true);
        $isIncoming = $dst !== '' && in_array($dst, $this->scopedExtensionKeys, true);

        // Most common cases:
        if ($isOutgoing && ! $isIncoming) {
            return ['label' => __('app.call_direction_outgoing'), 'color' => 'sky'];
        }
        if ($isIncoming && ! $isOutgoing) {
            return ['label' => __('app.call_direction_incoming'), 'color' => 'indigo'];
        }

        // Internal transfer or ambiguous rows.
        if ($isIncoming && $isOutgoing) {
            return ['label' => __('app.call_direction_internal'), 'color' => 'zinc'];
        }

        return ['label' => null, 'color' => 'zinc'];
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

    /**
     * شمارهٔ خام طرف تماس برای نمایش در tooltip وقتی عنوان، نام است نه خود شماره.
     */
    private function formatCallerRawForTooltip(string $raw): string
    {
        $trim = trim($raw);
        if ($trim === '') {
            return '';
        }

        $normalized = $this->normalizeExtensionCandidate($trim);
        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        if ($normalized !== '') {
            return $normalized;
        }

        return $digits !== '' ? $digits : $trim;
    }

    /**
     * فقط وقتی عنوان با شمارهٔ قابل‌فهم یکی نیست (مثلاً نام کاربر یا مخاطب)، شماره برای tooltip برگردانده می‌شود.
     */
    private function callerTooltipNumber(string $headingRaw, string $display): ?string
    {
        $tooltip = $this->formatCallerRawForTooltip($headingRaw);
        if ($tooltip === '') {
            return null;
        }

        $displayTrim = trim($display);
        if ($displayTrim === '' || $displayTrim === $tooltip) {
            return null;
        }

        $digitsDisplay = preg_replace('/\D+/', '', $displayTrim) ?? '';
        $digitsTooltip = preg_replace('/\D+/', '', $tooltip) ?? '';

        if ($digitsTooltip !== '' && $digitsDisplay === $digitsTooltip) {
            return null;
        }

        return $tooltip;
    }

    #[Layout('layouts.panels.crm')]
    public function render()
    {
        return view('livewire.panels.crm.dashboard.index');
    }
}
