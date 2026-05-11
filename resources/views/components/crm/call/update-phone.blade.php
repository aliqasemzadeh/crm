<?php

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\GNR\PartyPhone;
use App\Models\Sepidar\GNR\PartyRelated;
use App\Models\Voip\Phone;
use App\Support\IranPhoneNumberNormalizer;
use App\Support\PersianFinglishConverter;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    /** Must match `VOIP_PHONES_NUMBER_TO_NAME_CACHE_KEY` on the CRM dashboard page. */
    private const VOIP_PHONES_NUMBER_TO_NAME_CACHE_KEY = 'crm.voip_phones_number_to_name';

    public string $headingRaw = '';

    /** برچسب فعلی این طرف تماس در کارت (مثل نام مخاطب یا شماره خام). */
    public string $linkContextName = '';

    public string $normalizedPhone = '';

    public string $partySearch = '';

    public ?int $selectedPartyId = null;

    /** party | manual */
    public string $mode = 'party';

    public string $manualNameFa = '';

    public string $manualNameLatin = '';

    #[On('panels.crm.dashboard.index.link-phone')]
    public function open(string $raw = '', string $name = ''): void
    {
        $this->reset(['partySearch', 'selectedPartyId', 'mode', 'manualNameFa', 'manualNameLatin', 'linkContextName']);
        $this->mode = 'party';
        $this->headingRaw = $raw;
        $this->linkContextName = trim((string) $name);

        $normalized = IranPhoneNumberNormalizer::normalize($raw);

        if ($normalized === null || strlen($normalized) < 8) {
            Flux::toast(__('app.invalid_phone_for_link'), variant: 'danger');

            return;
        }

        $this->normalizedPhone = $normalized;

        $this->modal('crm-link-phone-modal')->show();
    }

    #[Computed]
    public function parties()
    {
        $term = trim((string) $this->partySearch);
        if (Str::length($term) < 2) {
            return collect();
        }

        $digitsOnly = preg_replace('/\D+/u', '', $term) ?? '';

        // جستجوی شماره: فقط PartyPhone (سبک‌تر)، بدون LIKE روی کل Party
        $looksLikePhoneQuery = $digitsOnly !== ''
            && strlen($digitsOnly) >= 4
            && preg_match('/^[\d\s\-+().]+$/u', $term) === 1;

        if ($looksLikePhoneQuery) {
            $searchDigits = IranPhoneNumberNormalizer::digitSearchStringsForQuery($term);
            if ($searchDigits === []) {
                return collect();
            }

            $cacheKey = 'crm.sepidar.party_ids_by_phone.'.md5(implode('|', $searchDigits));

            $partyIds = Cache::remember(
                $cacheKey,
                120,
                function () use ($searchDigits) {
                    $ids = [];
                    foreach ($searchDigits as $dig) {
                        if ($dig === '') {
                            continue;
                        }
                        $like = '%'.$dig.'%';
                        $ids = array_merge(
                            $ids,
                            PartyPhone::query()
                                ->where('Phone', 'like', $like)
                                ->pluck('PartyRef')
                                ->all()
                        );
                        $ids = array_merge(
                            $ids,
                            PartyRelated::query()
                                ->where('Phone', 'like', $like)
                                ->pluck('PartyRef')
                                ->all()
                        );
                    }

                    $ids = array_values(array_unique(array_filter($ids)));

                    return array_slice($ids, 0, 80);
                }
            );

            if ($partyIds === []) {
                return collect();
            }

            return Party::query()
                ->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])
                ->whereIn('PartyId', $partyIds)
                ->orderBy('PartyId')
                ->limit(25)
                ->get();
        }

        // جستجوی نام: فقط Party، بدون subquery روی PartyPhone
        return Party::query()
            ->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])
            ->where(function ($q) use ($term) {
                $q->where('Name', 'like', '%'.$term.'%')
                    ->orWhere('LastName', 'like', '%'.$term.'%')
                    ->orWhere('Name_En', 'like', '%'.$term.'%')
                    ->orWhere('LastName_En', 'like', '%'.$term.'%')
                    ->orWhereRaw("(ISNULL([Name], '') + ' ' + ISNULL([LastName], '')) LIKE ?", ['%'.$term.'%']);
            })
            ->orderBy('PartyId')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function selectedParty(): ?Party
    {
        if ($this->selectedPartyId === null) {
            return null;
        }

        return Party::query()->find($this->selectedPartyId);
    }

    public function suggestLatinFromFa(): void
    {
        $converter = new PersianFinglishConverter;
        $translated = trim($converter->convert($this->manualNameFa));
        $spaced = preg_replace('/\s+/u', ' ', $translated);
        $this->manualNameLatin = Str::title(is_string($spaced) ? $spaced : '');
    }

    public function submit(): void
    {
        if ($this->mode === 'manual') {
            $this->submitManual();

            return;
        }

        $this->submitParty();
    }

    private function submitParty(): void
    {
        $this->validate([
            'selectedPartyId' => ['required', 'integer'],
        ]);

        $normalized = IranPhoneNumberNormalizer::normalize($this->headingRaw);
        if ($normalized === null || $normalized !== $this->normalizedPhone) {
            Flux::toast(__('app.invalid_phone_for_link'), variant: 'danger');

            return;
        }

        try {
            $party = Party::query()->findOrFail($this->selectedPartyId);
            $name = $this->buildPartyDisplayName($party);

            $converter = new PersianFinglishConverter;
            $nameLatin = '';
            if ($name !== '') {
                $translated = trim($converter->convert($name));
                $spaced = preg_replace('/\s+/u', ' ', $translated);
                $nameLatin = Str::title(is_string($spaced) ? $spaced : '');
            }

            // Sepidar GNR.PartyPhone.Phone is varchar(20)
            $sepidarPhone = substr(IranPhoneNumberNormalizer::formatForSepidar($normalized), 0, 20);

            $partyPhone = PartyPhone::query()
                ->where('PartyRef', $party->PartyId)
                ->where('Phone', $sepidarPhone)
                ->first();

            if ($partyPhone === null) {
                $partyPhone = $this->createSepidarPartyPhone(
                    partyId: (int) $party->PartyId,
                    phone: $sepidarPhone,
                    type: $this->guessSepidarPhoneType($normalized),
                );
            }

            Phone::query()->updateOrCreate(
                ['number' => $normalized],
                [
                    'party_id' => $party->PartyId,
                    'party_phone_id' => $partyPhone->PartyPhoneId,
                    'party_related_id' => null,
                    'name' => $name,
                    'name_latin' => $nameLatin,
                    'is_manual' => false,
                ]
            );

            $this->forgetCachedPartyPhoneLookups($normalized, $sepidarPhone);

            Cache::forget(self::VOIP_PHONES_NUMBER_TO_NAME_CACHE_KEY);

            $this->modal('crm-link-phone-modal')->close();
            Flux::toast(__('app.phone_linked_to_party_success'));

            $this->redirect(route('panels.crm.dashboard.index'), navigate: false);
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.phone_link_failed'), variant: 'danger');
        }
    }

    private function submitManual(): void
    {
        $this->validate([
            'manualNameFa' => ['required', 'string', 'max:512'],
            'manualNameLatin' => ['required', 'string', 'max:512'],
        ]);

        $normalized = IranPhoneNumberNormalizer::normalize($this->headingRaw);
        if ($normalized === null || $normalized !== $this->normalizedPhone) {
            Flux::toast(__('app.invalid_phone_for_link'), variant: 'danger');

            return;
        }

        try {
            Phone::query()->updateOrCreate(
                ['number' => $normalized],
                [
                    'party_id' => null,
                    'party_phone_id' => null,
                    'party_related_id' => null,
                    'name' => trim($this->manualNameFa),
                    'name_latin' => trim($this->manualNameLatin),
                    'is_manual' => true,
                ]
            );

            Cache::forget(self::VOIP_PHONES_NUMBER_TO_NAME_CACHE_KEY);

            $this->modal('crm-link-phone-modal')->close();
            Flux::toast(__('app.manual_phone_saved_success'));

            $this->redirect(route('panels.crm.dashboard.index'), navigate: false);
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.phone_link_failed'), variant: 'danger');
        }
    }

    private function buildPartyDisplayName(Party $party): string
    {
        $parts = array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $p): bool => $p !== '');

        return trim(implode(' ', $parts));
    }

    private function forgetCachedPartyPhoneLookups(string $normalized, string $sepidarPhone): void
    {
        $terms = array_unique(array_filter([
            $normalized,
            $sepidarPhone,
            IranPhoneNumberNormalizer::formatForSepidar($normalized),
        ], static fn (string $s): bool => $s !== ''));

        $seen = [];
        foreach ($terms as $t) {
            $searchDigits = IranPhoneNumberNormalizer::digitSearchStringsForQuery($t);
            if ($searchDigits === []) {
                continue;
            }
            $cacheKey = 'crm.sepidar.party_ids_by_phone.'.md5(implode('|', $searchDigits));
            if (isset($seen[$cacheKey])) {
                continue;
            }
            $seen[$cacheKey] = true;
            Cache::forget($cacheKey);
        }
    }

    /**
     * Sepidar PartyPhone.Type heuristic: 1 (mobile) for 9xxxxxxxxx, otherwise 2 (landline).
     */
    private function guessSepidarPhoneType(string $normalized): int
    {
        return (strlen($normalized) === 10 && str_starts_with($normalized, '9')) ? 1 : 2;
    }

    /**
     * Insert a row in [GNR].[PartyPhone] with manual PK (column is NOT NULL and not Identity).
     * Retries on PK collisions to mitigate concurrent inserts.
     */
    private function createSepidarPartyPhone(int $partyId, string $phone, int $type): PartyPhone
    {
        $attempts = 0;
        $maxAttempts = 5;

        while (true) {
            $attempts++;

            try {
                return DB::connection('sqlsrv')->transaction(function () use ($partyId, $phone, $type) {
                    $nextId = (int) (PartyPhone::query()->max('PartyPhoneId') ?? 0) + 1;

                    return PartyPhone::create([
                        'PartyPhoneId' => $nextId,
                        'PartyRef' => $partyId,
                        'IsMain' => 0,
                        'Type' => $type,
                        'Phone' => $phone,
                        'Version' => 1,
                    ]);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                usleep(150_000);
            }
        }
    }
};
?>

<div>
    <flux:modal name="crm-link-phone-modal" flyout position="right" class="min-w-[420px] space-y-6">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.link_unknown_phone_title') }}</flux:heading>
                <flux:subheading class="mt-1">
                    {{ __('app.normalized_phone_key') }}:
                    <span dir="ltr" class="font-mono font-medium">{{ \App\Support\IranPhoneNumberNormalizer::displayForUi($normalizedPhone) }}</span>
                </flux:subheading>
                @if ($linkContextName !== '')
                    <flux:text size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                        {{ __('app.link_unknown_phone_call_label') }}
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $linkContextName }}</span>
                    </flux:text>
                @endif
                @if ($headingRaw !== '')
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        {{ __('app.raw_channel_value') }}:
                        <span dir="ltr" class="font-mono">{{ $headingRaw }}</span>
                    </flux:text>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:tooltip content="{{ __('app.link_phone_via_party') }}">
                    <flux:button
                        type="button"
                        size="sm"
                        variant="{{ $mode === 'party' ? 'primary' : 'ghost' }}"
                        color="{{ $mode === 'party' ? 'teal' : 'zinc' }}"
                        icon="link"
                        icon:variant="outline"
                        wire:click="$set('mode', 'party')"
                    />
                </flux:tooltip>
                <flux:tooltip content="{{ __('app.link_phone_manual_only') }}">
                    <flux:button
                        type="button"
                        size="sm"
                        variant="{{ $mode === 'manual' ? 'primary' : 'ghost' }}"
                        color="{{ $mode === 'manual' ? 'violet' : 'zinc' }}"
                        icon="user-plus"
                        icon:variant="outline"
                        wire:click="$set('mode', 'manual')"
                    />
                </flux:tooltip>
            </div>

            @if ($mode === 'party')
                <div class="space-y-2">
                    <flux:text size="sm" weight="medium">{{ __('app.search_party_for_phone') }}</flux:text>
                    <flux:text size="xs" class="text-zinc-500">{{ __('app.search_party_for_phone_hint') }}</flux:text>

                    <flux:select wire:model.live="selectedPartyId" variant="combobox" :filter="false" :placeholder="__('app.search_placeholder')">
                        <x-slot name="input">
                            <flux:select.input wire:model.live.debounce.400ms="partySearch" />
                        </x-slot>

                        @forelse ($this->parties as $party)
                            @php
                                $faName = trim(($party->Name ?? '').' '.($party->LastName ?? ''));
                                $enName = trim(($party->Name_En ?? '').' '.($party->LastName_En ?? ''));
                                $displayName = $faName !== '' ? $faName : ($enName !== '' ? $enName : __('app.no_name'));
                            @endphp
                            <flux:select.option
                                value="{{ $party->PartyId }}"
                                display="{{ $displayName }}"
                                wire:key="party-opt-{{ $party->PartyId }}"
                            >
                                <div class="flex flex-col">
                                    <span class="text-sm">{{ $displayName }}</span>
                                    @if ($enName !== '' && $enName !== $displayName)
                                        <span class="text-xs text-zinc-500" dir="ltr">{{ $enName }}</span>
                                    @endif
                                    <span class="text-[10px] text-zinc-400">PartyId #{{ $party->PartyId }}</span>
                                </div>
                            </flux:select.option>
                        @empty
                            @if (Str::length(trim((string) $partySearch)) >= 2)
                                <div class="px-3 py-2 text-xs text-zinc-500">{{ __('app.search_party_no_results') }}</div>
                            @endif
                        @endforelse
                    </flux:select>
                </div>

                @if ($this->selectedParty)
                    <flux:card class="space-y-1 text-sm">
                        <flux:heading size="sm">{{ __('app.selected_party') }}</flux:heading>
                        <div class="font-medium">
                            {{ trim(($this->selectedParty->Name ?? '').' '.($this->selectedParty->LastName ?? '')) ?: __('app.no_name') }}
                        </div>
                        <flux:text size="xs" class="text-zinc-500">PartyId: {{ $this->selectedParty->PartyId }}</flux:text>
                    </flux:card>
                @endif
            @else
                <div class="space-y-4">
                    <flux:input wire:model.live="manualNameFa" :label="__('app.display_name_fa')" maxlength="512" />

                    <div class="space-y-2">
                        <div class="flex items-end gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:input wire:model.live="manualNameLatin" :label="__('app.display_name_latin')" maxlength="512" />
                            </div>
                            <flux:tooltip content="{{ __('app.suggest_latin_from_fa') }}">
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    color="cyan"
                                    size="sm"
                                    icon="sparkles"
                                    icon:variant="outline"
                                    class="shrink-0"
                                    wire:click="suggestLatinFromFa"
                                />
                            </flux:tooltip>
                        </div>
                        <flux:text size="xs" class="text-zinc-500">{{ __('app.manual_phone_edit_latin_hint') }}</flux:text>
                    </div>
                </div>
            @endif

            <flux:button
                type="button"
                variant="primary"
                color="orange"
                class="w-full"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
            >
                <span wire:loading.remove wire:target="submit">{{ __('app.save') }}</span>
                <span wire:loading.flex wire:target="submit" class="items-center justify-center gap-2">
                    {{ __('app.loading') }}
                </span>
            </flux:button>
        </div>
    </flux:modal>
</div>
