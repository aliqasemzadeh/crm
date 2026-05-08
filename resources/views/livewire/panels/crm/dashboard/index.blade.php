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
