<?php

use App\Models\Issabel\Cdr;
use App\Models\Issabel\Device;
use App\Models\User;
use App\Models\Voip\Phone;
use App\Support\IranPhoneNumberNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

return new #[Layout('layouts.panels.crm')] class extends Component
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

    /** Cache key for VoIP phone book labels (clear after linking numbers). */
    public const VOIP_PHONES_NUMBER_TO_NAME_CACHE_KEY = 'crm.voip_phones_number_to_name';

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
        } catch (\Throwable $e) {
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
            self::VOIP_PHONES_NUMBER_TO_NAME_CACHE_KEY,
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
        $normalizedVoip = IranPhoneNumberNormalizer::normalize($headingRaw);
        $matchedVoip = $normalizedVoip !== null && isset($phoneMap[$normalizedVoip]);
        $canLinkUnknownPhone = ! $headingParty['is_internal_user']
            && $normalizedVoip !== null
            && strlen($normalizedVoip) >= 8
            && ! $matchedVoip;
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
            'heading_raw' => $headingRaw,
            'can_link_unknown_phone' => $canLinkUnknownPhone,
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

};
?>

<x-slot name="title">
    {{ __('common.calls_dashboard') }}
</x-slot>

<div x-data="{ showFilters: false }" class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <flux:heading size="lg">{{ __('common.calls_dashboard') }}</flux:heading>
        <flux:tooltip content="{{ __('app.call_filter_settings') }}">
            <flux:button
                type="button"
                variant="ghost"
                icon="cog-6-tooth"
                icon:variant="outline"
                x-on:click="showFilters = !showFilters"
            />
        </flux:tooltip>
    </div>

    @if ($this->isAdministrator)
        <flux:card x-show="showFilters" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <flux:select
                    wire:model.defer="userFilter"
                    searchable
                    placeholder="{{ __('app.filter_calls_by_user') }}"
                >
                    <option value="">{{ __('app.all_users') }}</option>
                    @foreach ($this->usersForFilter as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model.defer="directionFilter"
                    searchable
                    placeholder="{{ __('app.filter_calls_by_direction') }}"
                >
                    <option value="">{{ __('app.all_directions') }}</option>
                    <option value="incoming">{{ __('app.call_direction_incoming_only') }}</option>
                    <option value="outgoing">{{ __('app.call_direction_outgoing_only') }}</option>
                    <option value="internal">{{ __('app.call_direction_internal_only') }}</option>
                </flux:select>

                <div class="flex gap-2 md:justify-start">
                    <flux:tooltip content="{{ __('app.apply_filters') }}">
                        <flux:button
                            type="button"
                            size="sm"
                            variant="primary"
                            color="teal"
                            icon="filter"
                            icon:variant="outline"
                            wire:click="$refresh"
                            class="w-full"
                        />
                    </flux:tooltip>
                </div>
            </div>
        </flux:card>
    @endif

    @unless ($this->isAdministrator)
        <flux:card x-show="showFilters" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <flux:select
                    wire:model.defer="directionFilter"
                    searchable
                    placeholder="{{ __('app.filter_calls_by_direction') }}"
                >
                    <option value="">{{ __('app.all_directions') }}</option>
                    <option value="incoming">{{ __('app.call_direction_incoming_only') }}</option>
                    <option value="outgoing">{{ __('app.call_direction_outgoing_only') }}</option>
                    <option value="internal">{{ __('app.call_direction_internal_only') }}</option>
                </flux:select>

                <div class="md:col-span-2 flex gap-2 md:justify-start">
                    <flux:tooltip content="{{ __('app.apply_filters') }}">
                        <flux:button
                            type="button"
                            size="sm"
                            variant="primary"
                            color="teal"
                            icon="filter"
                            icon:variant="outline"
                            wire:click="$refresh"
                            class="w-full"
                        />
                    </flux:tooltip>
                </div>
            </div>
        </flux:card>
    @endunless

    @if ($loadError)
        <flux:callout variant="danger" icon="exclamation-triangle">
            {{ $loadError }}
        </flux:callout>
    @endif

    @if (count($calls) === 0 && ! $loadError)
        <flux:text class="text-zinc-500">{{ __('app.call_history_empty') }}</flux:text>
    @else
        <livewire:crm.call.send-sms :key="'send-sms-modal'" />
        <livewire:crm.call.update-phone :key="'crm-update-phone'" />

        <flux:timeline class="[--flux-timeline-item-gap:1rem]">
            @foreach ($calls as $call)
                <flux:timeline.item wire:key="cdr-{{ $call['uniqueid'] }}" align="start">
                    <flux:timeline.indicator :color="$call['indicator_color']">
                        <flux:icon.phone variant="micro" />
                    </flux:timeline.indicator>
                    <flux:timeline.content>
                        <div
                            class="min-w-0 space-y-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/80">
                            @php
                                $headingParty = $call['heading_party'] ?? [
                                    'display' => $call['phone_display'],
                                    'avatar_url' => null,
                                    'avatar_name' => $call['phone_display'],
                                    'is_internal_user' => false,
                                ];
                                $callerTip = $call['caller_tooltip_number'] ?? null;
                            @endphp

                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex shrink-0 items-center">
                                    @if ($headingParty['avatar_url'])
                                        <flux:avatar src="{{ $headingParty['avatar_url'] }}" size="xs" />
                                    @else
                                        <flux:avatar name="{{ $headingParty['avatar_name'] }}" color="auto" size="xs" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    @if ($callerTip)
                                        <flux:tooltip content="{{ $callerTip }}">
                                            <span class="block min-w-0 cursor-default outline-none" tabindex="0">
                                                <flux:heading size="sm" class="truncate leading-snug font-semibold text-zinc-900 dark:text-zinc-50">
                                                    {{ $call['phone_display'] }}
                                                </flux:heading>
                                            </span>
                                        </flux:tooltip>
                                    @else
                                        <flux:heading size="sm" class="truncate leading-snug font-semibold text-zinc-900 dark:text-zinc-50">
                                            {{ $call['phone_display'] }}
                                        </flux:heading>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon.calendar variant="micro" class="size-3.5 shrink-0 opacity-80" />
                                <span>{{ $call['jalali_datetime'] }}</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" :color="$call['indicator_color']">
                                    {{ $call['disposition_label'] }}
                                </flux:badge>
                                @php
                                    $isMobile = false;
                                    // بررسی شماره‌های مختلف برای یافتن شماره موبایل
                                    $mobilePhone = null;
                                    $candidatePhones = [
                                        $call['phone_display'] ?? '',
                                        $call['heading_party']['display'] ?? '',
                                        $call['caller_tooltip_number'] ?? '',
                                        $call['route_from_party']['display'] ?? '',
                                        $call['route_to_party']['display'] ?? '',
                                        $call['route_from_party']['tooltip_number'] ?? '',
                                        $call['route_to_party']['tooltip_number'] ?? '',
                                    ];

                                    foreach ($candidatePhones as $cp) {
                                        if (empty($cp)) continue;
                                        // حذف کاراکترهای غیر عددی به جز + در ابتدا
                                        $clean = preg_replace('/[^\d+]/', '', $cp);
                                        if (preg_match('/^(09|9|00989|\+989)\d{9}$/', $clean)) {
                                            $mobilePhone = $clean;
                                            $isMobile = true;
                                            break;
                                        }
                                    }

                                    $phone = $mobilePhone ?? $call['phone_display'] ?? '';
                                    $recipientName = $call['heading_party']['display'] ?? $phone;
                                @endphp
                                @if ($isMobile)
                                    <flux:tooltip content="{{ __('app.send_sms') }}">
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            color="blue"
                                            icon="message-square-text"
                                            icon:variant="outline"
                                            wire:click="$dispatch('panels.crm.dashboard.index.send-sms', { phone: '{{ $phone }}', name: '{{ $recipientName }}' })"
                                        />
                                    </flux:tooltip>
                                @endif
                                @if (! empty($call['can_link_unknown_phone']))
                                    <flux:tooltip content="{{ __('app.link_unknown_phone_tooltip') }}">
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            color="teal"
                                            icon="link"
                                            icon:variant="outline"
                                            wire:click="$dispatch('panels.crm.dashboard.index.link-phone', {{ \Illuminate\Support\Js::from(['raw' => $call['heading_raw']]) }})"
                                        />
                                    </flux:tooltip>
                                @endif
                                @if (! empty($call['direction_label']))
                                    <flux:badge size="sm" :color="$call['direction_color'] ?? 'zinc'">
                                        {{ $call['direction_label'] }}
                                    </flux:badge>
                                @endif
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('app.call_duration') }}
                                    <span dir="ltr" class="font-medium">{{ $call['duration_display'] }}</span>
                                </flux:text>
                            </div>

                            <div class="flex min-w-0 flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <span class="shrink-0 font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('app.call_route') }}
                                </span>
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    @php
                                        $fromParty = $call['route_from_party'] ?? [];
                                        $toParty = $call['route_to_party'] ?? [];
                                    @endphp

                                    @if (! empty($fromParty))
                                        @php
                                            $fromTooltip = isset($fromParty['tooltip_number']) && $fromParty['tooltip_number'] !== null && $fromParty['tooltip_number'] !== ''
                                                ? (string) $fromParty['tooltip_number']
                                                : null;
                                        @endphp
                                        @if ($fromTooltip)
                                            <flux:tooltip content="{{ $fromTooltip }}">
                                                <span class="inline-flex min-w-0 max-w-full cursor-default items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 outline-none dark:bg-zinc-700/70 dark:text-zinc-200"
                                                      tabindex="0">
                                                    <span class="truncate">{{ $fromParty['display'] ?? '' }}</span>
                                                </span>
                                            </flux:tooltip>
                                        @else
                                            <span class="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700/70 dark:text-zinc-200">
                                                <span class="truncate">{{ $fromParty['display'] ?? '' }}</span>
                                            </span>
                                        @endif
                                    @endif

                                    <flux:icon.arrow-right variant="micro" class="size-3.5 shrink-0 text-zinc-400" />

                                    @if (! empty($toParty))
                                        @php
                                            $toTooltip = isset($toParty['tooltip_number']) && $toParty['tooltip_number'] !== null && $toParty['tooltip_number'] !== ''
                                                ? (string) $toParty['tooltip_number']
                                                : null;
                                        @endphp
                                        @if ($toTooltip)
                                            <flux:tooltip content="{{ $toTooltip }}">
                                                <span class="inline-flex min-w-0 max-w-full cursor-default items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 outline-none dark:bg-zinc-700/70 dark:text-zinc-200"
                                                      tabindex="0">
                                                    <span class="truncate">{{ $toParty['display'] ?? '' }}</span>
                                                </span>
                                            </flux:tooltip>
                                        @else
                                            <span class="inline-flex min-w-0 max-w-full items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700/70 dark:text-zinc-200">
                                                <span class="truncate">{{ $toParty['display'] ?? '' }}</span>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </flux:timeline.content>
                </flux:timeline.item>
            @endforeach
        </flux:timeline>

        @if ($hasMore)
            <div
                wire:key="crm-cdr-sentinel"
                x-data
                x-init="
                    const el = $refs.sentinel;
                    if (!el) return;
                    let busy = false;
                    const io = new IntersectionObserver((entries) => {
                        for (const e of entries) {
                            if (!e.isIntersecting || busy) continue;
                            if (!$wire.hasMore) continue;
                            busy = true;
                            $wire.loadMore().finally(() => { busy = false; });
                            break;
                        }
                    }, { rootMargin: '160px', threshold: 0 });
                    io.observe(el);
                "
            >
                <div x-ref="sentinel" class="h-px w-full" aria-hidden="true"></div>
                <div wire:loading.flex wire:target="loadMore" class="justify-center py-3 text-sm text-zinc-500">
                    {{ __('app.loading') }}
                </div>
            </div>
        @endif
    @endif
</div>