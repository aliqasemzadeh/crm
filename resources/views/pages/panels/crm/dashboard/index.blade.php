<?php

use App\Ai\Agents\MissedCallAnalyst;
use App\Models\Issabel\Cdr;
use App\Models\Issabel\Device;
use App\Models\User;
use App\Models\Voip\Phone;
use App\Services\Crm\CallConversionStatsService;
use App\Services\Crm\HourlyCallPeakService;
use App\Services\Crm\MissedCallCsvExporter;
use App\Support\IranPhoneNumberNormalizer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Files\Document;
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

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    /**
     * @var array{
     *     summary: string,
     *     repeat_callers: list<array{phone: string, count: int, note: string}>,
     *     peak_insight: string,
     *     sms_draft: string,
     *     row_count: int,
     *     truncated: bool
     * }|null
     */
    public ?array $aiReport = null;

    public ?string $aiError = null;

    public bool $aiLoading = false;

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

    private const INTERNAL_USER_PROFILES_BY_EXTENSION_CACHE_KEY = 'crm.users_by_internal_phone_extension_v3';

    private const PHONES_CACHE_TTL_SECONDS = 600;

    protected function queryString(): array
    {
        $now = Jalalian::now();
        $monthStart = $now->getFirstDayOfMonth()->format('Y/m/d');
        $monthEnd = $now->getEndDayOfMonth()->format('Y/m/d');

        return [
            'dateStart' => ['except' => $monthStart],
            'dateEnd' => ['except' => $monthEnd],
            'userFilter' => ['except' => null],
            'directionFilter' => ['except' => null],
        ];
    }

    public function mount(): void
    {
        if (! request()->hasAny(['dateStart', 'dateEnd'])) {
            $now = Jalalian::now();
            $this->dateStart = $now->getFirstDayOfMonth()->format('Y/m/d');
            $this->dateEnd = $now->getEndDayOfMonth()->format('Y/m/d');
        }

        $this->fetchBatch(true);
    }

    public function updatedDateStart(): void
    {
        unset($this->performanceStats, $this->hourlyPeakChart);
        $this->aiReport = null;
        $this->aiError = null;
    }

    public function updatedDateEnd(): void
    {
        unset($this->performanceStats, $this->hourlyPeakChart);
        $this->aiReport = null;
        $this->aiError = null;
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

        if ($this->userFilter === null || $this->userFilter === '' || (int) $this->userFilter <= 0) {
            $this->userFilter = null;
        } else {
            $this->userFilter = (int) $this->userFilter;
        }

        unset($this->performanceStats, $this->hourlyPeakChart);
        $this->aiReport = null;
        $this->aiError = null;
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
        $user->loadMissing('internalPhones');
        $devices = $user->internalPhoneDevices();
        if ($devices->isEmpty()) {
            $this->scopedExtensionKeys = [];
            $query->whereRaw('1 = 0');

            return;
        }

        $keys = $this->extensionKeysFromDevices($devices);
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
            ->whereHas('internalPhones')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'avatar']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function usersForPerformanceStats()
    {
        $authUser = auth()->user();
        if (! $authUser instanceof User) {
            return collect();
        }

        if ($this->isAdministrator) {
            $query = User::query()
                ->whereHas('internalPhones')
                ->with('internalPhones')
                ->orderBy('first_name')
                ->orderBy('last_name');

            if ($this->userFilter !== null) {
                $query->whereKey($this->userFilter);
            }

            return $query->get();
        }

        $authUser->loadMissing('internalPhones');
        if ($authUser->internalPhones->isEmpty()) {
            return collect();
        }

        return collect([$authUser]);
    }

    /**
     * @return array{
     *     error: ?string,
     *     totals: array{
     *         successful_calls: int,
     *         rejected_calls: int,
     *         invoices_count: int,
     *         rejection_rate: ?float,
     *         conversion_rate: ?float
     *     },
     *     rows: list<array{
     *         user_id: int,
     *         name: string,
     *         avatar_url: ?string,
     *         successful_calls: int,
     *         rejected_calls: int,
     *         invoices_count: int,
     *         rejection_rate: ?float,
     *         conversion_rate: ?float
     *     }>
     * }
     */
    #[Computed]
    public function performanceStats(): array
    {
        $empty = [
            'error' => null,
            'totals' => [
                'successful_calls' => 0,
                'rejected_calls' => 0,
                'invoices_count' => 0,
                'rejection_rate' => null,
                'conversion_rate' => null,
            ],
            'rows' => [],
        ];

        try {
            $start = $this->dateStart
                ? Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay()
                : Jalalian::now()->getFirstDayOfMonth()->toCarbon()->startOfDay();
            $end = $this->dateEnd
                ? Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay()
                : Jalalian::now()->getEndDayOfMonth()->toCarbon()->endOfDay();
        } catch (\Throwable) {
            $empty['error'] = __('app.call_performance_stats_load_failed');

            return $empty;
        }

        try {
            $stats = app(CallConversionStatsService::class)
                ->forUsers($this->usersForPerformanceStats(), $start, $end);

            return [
                'error' => null,
                'totals' => $stats['totals'],
                'rows' => $stats['rows'],
            ];
        } catch (\Throwable $e) {
            report($e);
            $empty['error'] = __('app.call_performance_stats_load_failed');

            return $empty;
        }
    }

    /**
     * نرخ تبدیل به مشتری — بالاتر بهتر است.
     *
     * @return 'emerald'|'amber'|'rose'|'zinc'
     */
    public function conversionBadgeColor(?float $rate): string
    {
        if ($rate === null) {
            return 'zinc';
        }

        if ($rate >= 10) {
            return 'emerald';
        }

        if ($rate > 0) {
            return 'amber';
        }

        return 'rose';
    }

    /**
     * نرخ رد تماس — بالاتر بدتر است.
     *
     * @return 'emerald'|'amber'|'rose'|'zinc'
     */
    public function rejectionBadgeColor(?float $rate): string
    {
        if ($rate === null) {
            return 'zinc';
        }

        if ($rate >= 50) {
            return 'rose';
        }

        if ($rate >= 25) {
            return 'amber';
        }

        return 'emerald';
    }

    public function formatPercentRate(?float $rate): string
    {
        if ($rate === null) {
            return '—';
        }

        return number_format($rate, 1).'٪';
    }

    /** @deprecated Use formatPercentRate */
    public function formatConversionRate(?float $rate): string
    {
        return $this->formatPercentRate($rate);
    }

    /**
     * @return array{card: string, text: string}
     */
    public function rateHeroClasses(string $color): array
    {
        return match ($color) {
            'emerald' => [
                'card' => 'border-emerald-200 dark:border-emerald-800 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-950/40 dark:to-zinc-900',
                'text' => 'text-emerald-600 dark:text-emerald-400',
            ],
            'amber' => [
                'card' => 'border-amber-200 dark:border-amber-800 bg-gradient-to-br from-amber-50 to-white dark:from-amber-950/40 dark:to-zinc-900',
                'text' => 'text-amber-600 dark:text-amber-400',
            ],
            'rose' => [
                'card' => 'border-rose-200 dark:border-rose-800 bg-gradient-to-br from-rose-50 to-white dark:from-rose-950/40 dark:to-zinc-900',
                'text' => 'text-rose-600 dark:text-rose-400',
            ],
            default => [
                'card' => 'border-zinc-200 dark:border-zinc-700 bg-gradient-to-br from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-950',
                'text' => 'text-zinc-600 dark:text-zinc-300',
            ],
        };
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null
     */
    private function reportDateRange(): ?array
    {
        try {
            $start = $this->dateStart
                ? Jalalian::fromFormat('Y/m/d', $this->dateStart)->toCarbon()->startOfDay()
                : Jalalian::now()->getFirstDayOfMonth()->toCarbon()->startOfDay();
            $end = $this->dateEnd
                ? Jalalian::fromFormat('Y/m/d', $this->dateEnd)->toCarbon()->endOfDay()
                : Jalalian::now()->getEndDayOfMonth()->toCarbon()->endOfDay();

            return [$start, $end];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{hour: string, total: int, missed: int}>
     */
    #[Computed]
    public function hourlyPeakChart(): array
    {
        $range = $this->reportDateRange();
        if ($range === null) {
            return [];
        }

        [$start, $end] = $range;

        try {
            return app(HourlyCallPeakService::class)
                ->forUsers($this->usersForPerformanceStats(), $start, $end);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public function analyzeMissedCalls(): void
    {
        $this->aiLoading = true;
        $this->aiError = null;

        try {
            $range = $this->reportDateRange();
            if ($range === null) {
                $this->aiError = __('app.missed_call_ai_invalid_range');
                Flux::toast(__('app.missed_call_ai_invalid_range'), variant: 'danger');

                return;
            }

            [$start, $end] = $range;
            $users = $this->usersForPerformanceStats();
            if ($users->isEmpty()) {
                $this->aiError = __('app.missed_call_ai_no_users');
                Flux::toast(__('app.missed_call_ai_no_users'), variant: 'danger');

                return;
            }

            $export = app(MissedCallCsvExporter::class)->export($users, $start, $end);
            if ($export['row_count'] === 0) {
                $this->aiReport = [
                    'summary' => __('app.missed_call_ai_empty_summary'),
                    'repeat_callers' => [],
                    'peak_insight' => __('app.missed_call_ai_empty_peak'),
                    'sms_draft' => '',
                    'row_count' => 0,
                    'truncated' => false,
                ];
                Flux::toast(__('app.missed_call_ai_empty_toast'));

                return;
            }

            $prompt = __('app.missed_call_ai_prompt', [
                'from' => $this->dateStart ?? '',
                'to' => $this->dateEnd ?? '',
                'count' => $export['row_count'],
            ]);

            $response = (new MissedCallAnalyst)->prompt(
                $prompt,
                attachments: [
                    Document::fromString($export['csv'], 'text/csv'),
                ],
                timeout: 120,
            );

            $repeatCallers = collect($response['repeat_callers'] ?? [])
                ->map(fn ($row): array => [
                    'phone' => (string) ($row['phone'] ?? ''),
                    'count' => (int) ($row['count'] ?? 0),
                    'note' => (string) ($row['note'] ?? ''),
                ])
                ->filter(fn (array $row): bool => $row['phone'] !== '')
                ->values()
                ->all();

            $this->aiReport = [
                'summary' => (string) ($response['summary'] ?? ''),
                'repeat_callers' => $repeatCallers,
                'peak_insight' => (string) ($response['peak_insight'] ?? ''),
                'sms_draft' => (string) ($response['sms_draft'] ?? ''),
                'row_count' => $export['row_count'],
                'truncated' => $export['truncated'],
            ];

            Flux::toast(__('app.missed_call_ai_success'));
        } catch (\Throwable $e) {
            report($e);
            $this->aiReport = null;
            $this->aiError = __('app.missed_call_ai_failed');
            Flux::toast(__('app.missed_call_ai_failed'), variant: 'danger');
        } finally {
            $this->aiLoading = false;
        }
    }

    public function copySmsDraft(): void
    {
        if (blank($this->aiReport['sms_draft'] ?? null)) {
            return;
        }

        Flux::toast(__('app.missed_call_ai_sms_copied'));
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
     * پروفایل کاربر CRM برای داخلی‌های متصل از طریق user_internal_phones → Issabel devices.
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
                    ->whereHas('internalPhones')
                    ->with('internalPhones')
                    ->get();

                foreach ($users as $user) {
                    $devices = $user->internalPhoneDevices();
                    if ($devices->isEmpty()) {
                        continue;
                    }

                    $profile = [
                        'name' => $user->name,
                        'avatar_url' => $user->getAvatarUrl(),
                    ];

                    foreach ($this->extensionKeysFromDevices($devices) as $key) {
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
     * @param  \Illuminate\Support\Collection<int, Device>  $devices
     * @return list<string>
     */
    private function extensionKeysFromDevices($devices): array
    {
        $keys = [];
        foreach ($devices as $device) {
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
     * آیا این پایهٔ خام CDR را می‌توان به مخاطب وصل کرد (غیر داخلی، شمارهٔ واقعی، هنوز در دفترچه نیست).
     *
     * @param  array<string, string>  $deviceMap
     * @param  array<string, string>  $phoneMap
     * @param  array<string, array{name: string, avatar_url: ?string}>  $internalProfiles
     * @return array{can: bool, display: string}
     */
    private function linkUnknownPhoneMetaForRaw(
        string $raw,
        array $deviceMap,
        array $phoneMap,
        array $internalProfiles,
    ): array {
        $trim = trim($raw);
        if ($trim === '') {
            return ['can' => false, 'display' => ''];
        }

        $phoneDisplay = $this->resolvePartyLabel($trim, $deviceMap, $phoneMap);
        $partyMeta = $this->partyPresentation($trim, $phoneDisplay, $internalProfiles);
        $normalizedVoip = IranPhoneNumberNormalizer::normalize($trim);
        $matchedVoip = $normalizedVoip !== null && isset($phoneMap[$normalizedVoip]);

        $can = ! $partyMeta['is_internal_user']
            && $normalizedVoip !== null
            && strlen($normalizedVoip) >= 8
            && ! $matchedVoip;

        return ['can' => $can, 'display' => $partyMeta['display']];
    }

    /**
     * آیا این پایهٔ CDR همان داخلی کاربر جاری است (با همان کلیدهای scoped که برای فیلتر جهت استفاده می‌شود).
     */
    private function cdrLegMatchesScopedExtension(string $leg): bool
    {
        if ($leg === '' || $this->scopedExtensionKeys === []) {
            return false;
        }

        $trim = trim($leg);
        if ($trim === '') {
            return false;
        }

        if (in_array($trim, $this->scopedExtensionKeys, true)) {
            return true;
        }

        foreach ($this->partyLookupKeys($trim) as $key) {
            if (in_array($key, $this->scopedExtensionKeys, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * تشخیص احتمالی پایهٔ داخلی کوتاه (بدون زمینهٔ کاربر) برای حالت «همه تماس‌ها» ادمین.
     */
    private function looksLikeShortExtensionLeg(string $leg): bool
    {
        $trim = trim($leg);
        if ($trim === '') {
            return false;
        }

        $norm = $this->normalizeExtensionCandidate($trim);
        if ($norm !== '' && preg_match('/^\d{1,6}$/', $norm) === 1) {
            return strlen($norm) <= 5;
        }

        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        return $digits !== '' && strlen($digits) <= 5;
    }

    /**
     * طرف تماس «نام‌گذاری / ثبت ناشناس»: ورودی → معمولاً src؛ خروجی → معمولاً dst؛ قبلاً فقط cnum/src بود و خروجی بی‌خبر می‌ماند.
     */
    private function resolvePrimaryPartyRawForCard(Cdr $row): string
    {
        $cnum = trim((string) $row->cnum);
        $src = trim((string) $row->src);
        $dst = trim((string) $row->dst);
        $fallback = $cnum !== '' ? $cnum : $src;

        if ($this->scopedExtensionKeys !== []) {
            $srcScoped = $this->cdrLegMatchesScopedExtension($src);
            $dstScoped = $this->cdrLegMatchesScopedExtension($dst);

            if ($srcScoped && ! $dstScoped && $dst !== '') {
                $norm = IranPhoneNumberNormalizer::normalize($dst);

                if ($norm !== null && strlen($norm) >= 8) {
                    return $dst;
                }

                return $fallback;
            }

            if ($dstScoped && ! $srcScoped && $src !== '') {
                $norm = IranPhoneNumberNormalizer::normalize($src);

                if ($norm !== null && strlen($norm) >= 8) {
                    return $src;
                }

                return $fallback;
            }

            return $fallback;
        }

        $srcNorm = IranPhoneNumberNormalizer::normalize($src);
        $dstNorm = IranPhoneNumberNormalizer::normalize($dst);

        if ($this->looksLikeShortExtensionLeg($src) && $dstNorm !== null && strlen($dstNorm) >= 8
            && ($srcNorm === null || strlen($srcNorm) < 8)) {
            return $dst;
        }

        if ($this->looksLikeShortExtensionLeg($dst) && $srcNorm !== null && strlen($srcNorm) >= 8
            && ($dstNorm === null || strlen($dstNorm) < 8)) {
            return $src;
        }

        return $fallback;
    }

    /**
     * @param  array<string, string>  $phoneMap
     * @param  array<string, string>  $deviceMap
     * @param  array<string, array{name: string, avatar_url: ?string}>  $internalProfiles
     * @return array<string, mixed>
     */
    private function mapRow(Cdr $row, array $phoneMap, array $deviceMap, array $internalProfiles): array
    {
        $appearance = $this->dispositionAppearance((string) $row->disposition);
        $billsec = (int) $row->billsec;

        $direction = $this->callDirectionForScopedUser($row);

        // جهت نمایش مسیر: مقصد CDR سمت چپ، مبدأ سمت راست (مثلاً 32319051 → نام داخلی)
        $routeFromLabel = $this->resolvePartyLabel((string) $row->dst, $deviceMap, $phoneMap);
        $routeToLabel = $this->resolvePartyLabel((string) $row->src, $deviceMap, $phoneMap);

        // عنوان کارت: نام واقعی (devices / CRM)؛ اگر هنوز فقط شمارهٔ کوتاه بود از طرف‌های مسیر کمک بگیر
        $primaryPartyRaw = $this->resolvePrimaryPartyRawForCard($row);
        $phoneDisplay = $this->resolveHeadingDisplay(
            $primaryPartyRaw,
            (string) $row->src,
            (string) $row->dst,
            $deviceMap,
            $phoneMap,
            $routeToLabel,
            $routeFromLabel
        );

        $headingRaw = $primaryPartyRaw;
        $headingParty = $this->partyPresentation($headingRaw, $phoneDisplay, $internalProfiles);
        $callerTooltipNumber = $this->callerTooltipNumber($headingRaw, $headingParty['display']);

        $routeFromParty = $this->partyPresentation((string) $row->dst, $routeFromLabel, $internalProfiles);
        $routeFromParty['tooltip_number'] = $this->callerTooltipNumber((string) $row->dst, $routeFromParty['display']);
        $fromLinkMeta = $this->linkUnknownPhoneMetaForRaw((string) $row->dst, $deviceMap, $phoneMap, $internalProfiles);
        $routeFromParty['can_link_unknown_phone'] = $fromLinkMeta['can'];
        $routeFromParty['link_raw'] = trim((string) $row->dst);
        $routeFromParty['link_display_name'] = $fromLinkMeta['display'];

        $routeToParty = $this->partyPresentation((string) $row->src, $routeToLabel, $internalProfiles);
        $routeToParty['tooltip_number'] = $this->callerTooltipNumber((string) $row->src, $routeToParty['display']);
        $toLinkMeta = $this->linkUnknownPhoneMetaForRaw((string) $row->src, $deviceMap, $phoneMap, $internalProfiles);
        $routeToParty['can_link_unknown_phone'] = $toLinkMeta['can'];
        $routeToParty['link_raw'] = trim((string) $row->src);
        $routeToParty['link_display_name'] = $toLinkMeta['display'];

        $headingLinkMeta = $this->linkUnknownPhoneMetaForRaw($headingRaw, $deviceMap, $phoneMap, $internalProfiles);
        $primaryLinkRaw = '';
        $primaryLinkDisplayName = '';
        if ($headingLinkMeta['can']) {
            $primaryLinkRaw = trim($headingRaw);
            $primaryLinkDisplayName = $headingLinkMeta['display'];
        } else {
            foreach ([trim((string) $row->src), trim((string) $row->dst)] as $leg) {
                if ($leg === '') {
                    continue;
                }
                $legMeta = $this->linkUnknownPhoneMetaForRaw($leg, $deviceMap, $phoneMap, $internalProfiles);
                if ($legMeta['can']) {
                    $primaryLinkRaw = $leg;
                    $primaryLinkDisplayName = $legMeta['display'];
                    break;
                }
            }
        }
        $canLinkUnknownPhone = $primaryLinkRaw !== '';

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
            'link_unknown_raw' => $primaryLinkRaw,
            'link_unknown_display_name' => $primaryLinkDisplayName,
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

        $isOutgoing = $src !== '' && $this->cdrLegMatchesScopedExtension($src);
        $isIncoming = $dst !== '' && $this->cdrLegMatchesScopedExtension($dst);

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

        foreach (IranPhoneNumberNormalizer::lookupKeyVariants($trim) as $v) {
            if ($v !== '' && ! in_array($v, $keys, true)) {
                $keys[] = $v;
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

    @php
        $stats = $this->performanceStats;
        $totals = $stats['totals'];
        $conversionColor = $this->conversionBadgeColor($totals['conversion_rate']);
        $rejectionColor = $this->rejectionBadgeColor($totals['rejection_rate']);
        $conversionHero = $this->rateHeroClasses($conversionColor);
        $rejectionHero = $this->rateHeroClasses($rejectionColor);
        $scopedToOneUser = $this->isAdministrator && $this->userFilter !== null;
    @endphp

    <flux:card class="panel-filter-card">
        <div @class([
            'grid grid-cols-1 gap-3 items-end',
            'md:grid-cols-3' => $this->isAdministrator,
            'md:grid-cols-2' => ! $this->isAdministrator,
        ])>
            <div wire:key="crm-stats-date-range-{{ $dateStart ?? 'all' }}-{{ $dateEnd ?? 'all' }}">
                <x-jalali-date-range
                    :label="__('app.date_range')"
                    :start="$dateStart"
                    :end="$dateEnd"
                    wire:model.start="dateStart"
                    wire:model.end="dateEnd"
                />
            </div>
            @if ($this->isAdministrator)
                <flux:select
                    wire:model.live="userFilter"
                    searchable
                    :label="__('app.filter_report_by_user')"
                    placeholder="{{ __('app.filter_report_by_user') }}"
                >
                    <flux:select.option value="">{{ __('app.all_users') }}</flux:select.option>
                    @foreach ($this->usersForFilter as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
            <div class="flex flex-col gap-2">
                <flux:button
                    type="button"
                    variant="primary"
                    color="violet"
                    icon="sparkles"
                    icon:variant="outline"
                    wire:click="analyzeMissedCalls"
                    wire:loading.attr="disabled"
                    wire:target="analyzeMissedCalls"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="analyzeMissedCalls">{{ __('app.missed_call_ai_analyze') }}</span>
                    <span wire:loading wire:target="analyzeMissedCalls">{{ __('app.missed_call_ai_analyzing') }}</span>
                </flux:button>
                <div class="space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                    <div>{{ __('app.conversion_rate_formula_hint') }}</div>
                    <div>{{ __('app.rejection_rate_formula_hint') }}</div>
                </div>
            </div>
        </div>
    </flux:card>

    @if ($stats['error'])
        <flux:callout variant="danger" icon="exclamation-triangle">
            {{ $stats['error'] }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <flux:card class="{{ $conversionHero['card'] }}">
            <div class="flex flex-col gap-2">
                <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                    @if ($scopedToOneUser)
                        {{ __('app.conversion_rate') }}
                    @elseif ($this->isAdministrator)
                        {{ __('app.team_conversion_rate') }}
                    @else
                        {{ __('app.conversion_rate') }}
                    @endif
                </flux:text>
                <span class="text-5xl font-bold tabular-nums tracking-tight {{ $conversionHero['text'] }}">
                    {{ $this->formatPercentRate($totals['conversion_rate']) }}
                </span>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('app.conversion_rate_to_customer_hint') }}
                </flux:text>
            </div>
        </flux:card>

        <flux:card class="{{ $rejectionHero['card'] }}">
            <div class="flex flex-col gap-2">
                <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                    @if ($scopedToOneUser)
                        {{ __('app.rejection_rate') }}
                    @elseif ($this->isAdministrator)
                        {{ __('app.team_rejection_rate') }}
                    @else
                        {{ __('app.rejection_rate') }}
                    @endif
                </flux:text>
                <span class="text-5xl font-bold tabular-nums tracking-tight {{ $rejectionHero['text'] }}">
                    {{ $this->formatPercentRate($totals['rejection_rate']) }}
                </span>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('app.rejection_rate_formula_hint') }}
                </flux:text>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <div class="space-y-1">
                <flux:text class="text-sm text-zinc-500">{{ __('app.successful_calls') }}</flux:text>
                <flux:heading size="xl" class="tabular-nums text-green-600 dark:text-green-400">
                    {{ number_format($totals['successful_calls']) }}
                </flux:heading>
            </div>
        </flux:card>
        <flux:card>
            <div class="space-y-1">
                <flux:text class="text-sm text-zinc-500">{{ __('app.rejected_calls') }}</flux:text>
                <flux:heading size="xl" class="tabular-nums text-rose-600 dark:text-rose-400">
                    {{ number_format($totals['rejected_calls']) }}
                </flux:heading>
            </div>
        </flux:card>
        <flux:card>
            <div class="space-y-1">
                <flux:text class="text-sm text-zinc-500">{{ __('app.invoices_count') }}</flux:text>
                <flux:heading size="xl" class="tabular-nums text-teal-600 dark:text-teal-400">
                    {{ number_format($totals['invoices_count']) }}
                </flux:heading>
            </div>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="sm" class="mb-4">{{ __('app.call_performance_by_user') }}</flux:heading>
        <div class="overflow-x-auto -mx-1 px-1">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="min-w-44 whitespace-nowrap">{{ __('app.user') }}</flux:table.column>
                    <flux:table.column align="end" class="whitespace-nowrap">{{ __('app.successful_calls') }}</flux:table.column>
                    <flux:table.column align="end" class="whitespace-nowrap">{{ __('app.rejected_calls') }}</flux:table.column>
                    <flux:table.column align="end" class="whitespace-nowrap">{{ __('app.rejection_rate') }}</flux:table.column>
                    <flux:table.column align="end" class="whitespace-nowrap">{{ __('app.invoice_count') }}</flux:table.column>
                    <flux:table.column align="end" class="whitespace-nowrap">{{ __('app.conversion_rate') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($stats['rows'] as $row)
                        <flux:table.row wire:key="perf-{{ $row['user_id'] }}">
                            <flux:table.cell class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if ($row['avatar_url'])
                                        <flux:avatar src="{{ $row['avatar_url'] }}" size="xs" class="shrink-0" />
                                    @else
                                        <flux:avatar name="{{ $row['name'] }}" color="auto" size="xs" class="shrink-0" />
                                    @endif
                                    <span class="font-medium">{{ $row['name'] }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap tabular-nums text-green-600 dark:text-green-400">
                                {{ number_format($row['successful_calls']) }}
                            </flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap tabular-nums text-rose-600 dark:text-rose-400">
                                {{ number_format($row['rejected_calls']) }}
                            </flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap">
                                <div class="inline-flex justify-end">
                                    <flux:badge size="sm" :color="$this->rejectionBadgeColor($row['rejection_rate'])" class="font-semibold tabular-nums">
                                        {{ $this->formatPercentRate($row['rejection_rate']) }}
                                    </flux:badge>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap tabular-nums text-teal-600 dark:text-teal-400">
                                {{ number_format($row['invoices_count']) }}
                            </flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap">
                                <div class="inline-flex justify-end">
                                    <flux:badge size="sm" :color="$this->conversionBadgeColor($row['conversion_rate'])" class="font-semibold tabular-nums">
                                        {{ $this->formatPercentRate($row['conversion_rate']) }}
                                    </flux:badge>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <flux:text class="text-zinc-500">{{ __('app.call_performance_empty') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <flux:separator variant="subtle" />

    <flux:heading size="md">{{ __('app.call_history_timeline') }}</flux:heading>

    @if ($this->isAdministrator)
        <flux:card class="panel-filter-card" x-show="showFilters" x-cloak>
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
        <flux:card class="panel-filter-card" x-show="showFilters" x-cloak>
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
                                            wire:click="$dispatch('panels.crm.dashboard.index.link-phone', {{ \Illuminate\Support\Js::from(['raw' => $call['link_unknown_raw'] ?? $call['heading_raw'], 'name' => $call['link_unknown_display_name'] ?? '']) }})"
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
                                        <span class="inline-flex min-w-0 max-w-full items-center gap-1">
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
                                            @if (! empty($fromParty['can_link_unknown_phone']))
                                                <flux:tooltip content="{{ __('app.link_unknown_phone_tooltip') }}">
                                                    <flux:button
                                                        size="xs"
                                                        variant="primary"
                                                        color="teal"
                                                        icon="link"
                                                        icon:variant="outline"
                                                        wire:click="$dispatch('panels.crm.dashboard.index.link-phone', {{ \Illuminate\Support\Js::from(['raw' => $fromParty['link_raw'] ?? '', 'name' => $fromParty['link_display_name'] ?? ($fromParty['display'] ?? '')]) }})"
                                                    />
                                                </flux:tooltip>
                                            @endif
                                        </span>
                                    @endif

                                    <flux:icon.arrow-right variant="micro" class="size-3.5 shrink-0 text-zinc-400" />

                                    @if (! empty($toParty))
                                        @php
                                            $toTooltip = isset($toParty['tooltip_number']) && $toParty['tooltip_number'] !== null && $toParty['tooltip_number'] !== ''
                                                ? (string) $toParty['tooltip_number']
                                                : null;
                                        @endphp
                                        <span class="inline-flex min-w-0 max-w-full items-center gap-1">
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
                                            @if (! empty($toParty['can_link_unknown_phone']))
                                                <flux:tooltip content="{{ __('app.link_unknown_phone_tooltip') }}">
                                                    <flux:button
                                                        size="xs"
                                                        variant="primary"
                                                        color="teal"
                                                        icon="link"
                                                        icon:variant="outline"
                                                        wire:click="$dispatch('panels.crm.dashboard.index.link-phone', {{ \Illuminate\Support\Js::from(['raw' => $toParty['link_raw'] ?? '', 'name' => $toParty['link_display_name'] ?? ($toParty['display'] ?? '')]) }})"
                                                    />
                                                </flux:tooltip>
                                            @endif
                                        </span>
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