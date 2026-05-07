<x-slot name="title">
    {{ __('common.calls_dashboard') }}
</x-slot>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <flux:heading size="lg">{{ __('common.calls_dashboard') }}</flux:heading>
        <flux:tooltip content="{{ __('common.settings') }}">
            <flux:button type="button" variant="ghost" icon="cog-6-tooth" icon:variant="outline" />
        </flux:tooltip>
    </div>

    @if ($this->isAdministrator)
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <flux:select
                    wire:model.live="userFilter"
                    searchable
                    placeholder="{{ __('app.filter_calls_by_user') }}"
                >
                    <option value="">{{ __('app.all_users') }}</option>
                    @foreach ($this->usersForFilter as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model.live="directionFilter"
                    placeholder="{{ __('app.filter_calls_by_direction') }}"
                >
                    <option value="">{{ __('app.all_directions') }}</option>
                    <option value="incoming">{{ __('app.call_direction_incoming_only') }}</option>
                    <option value="outgoing">{{ __('app.call_direction_outgoing_only') }}</option>
                    <option value="internal">{{ __('app.call_direction_internal_only') }}</option>
                </flux:select>

                <flux:text class="md:col-span-3 text-sm text-zinc-500">
                    {{ __('app.admin_can_view_all_calls') }}
                </flux:text>
            </div>
        </flux:card>
    @endif

    @unless ($this->isAdministrator)
        <flux:card>
            <div class="grid grid-cols-1 gap-3">
                <flux:select
                    wire:model.live="directionFilter"
                    placeholder="{{ __('app.filter_calls_by_direction') }}"
                >
                    <option value="">{{ __('app.all_directions') }}</option>
                    <option value="incoming">{{ __('app.call_direction_incoming_only') }}</option>
                    <option value="outgoing">{{ __('app.call_direction_outgoing_only') }}</option>
                    <option value="internal">{{ __('app.call_direction_internal_only') }}</option>
                </flux:select>
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
                                <flux:badge size="sm" :color="$call['badge_color']">
                                    {{ $call['disposition_label'] }}
                                </flux:badge>
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

                            <flux:text class="inline-flex min-w-0 flex-wrap items-start gap-x-1 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <span class="shrink-0 font-medium text-zinc-700 dark:text-zinc-300">{{ __('app.call_route') }}</span>
                                <span class="inline-flex max-w-full flex-wrap items-center gap-x-1 gap-y-1">
                                    <x-crm.call-route-party :party="$call['route_from_party'] ?? []" />
                                    <span class="mx-0.5 font-normal text-zinc-400">→</span>
                                    <x-crm.call-route-party :party="$call['route_to_party'] ?? []" />
                                </span>
                            </flux:text>
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
