<x-slot name="title">
    {{ __('app.call_history_timeline') }}
</x-slot>

<div class="space-y-6">
    <flux:heading size="lg">{{ __('app.call_history_timeline') }}</flux:heading>

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
                @php
                    $disp = strtoupper(trim($call['disposition_raw'] ?? ''));
                    $cornerBadge =
                        match ($disp) {
                            'ANSWERED' => 'bg-emerald-500 dark:bg-emerald-600',
                            'NO ANSWER' => 'bg-rose-500 dark:bg-rose-600',
                            'BUSY' => 'bg-amber-500 dark:bg-amber-600',
                            'FAILED' => 'bg-rose-600 dark:bg-rose-700',
                            'CONGESTION' => 'bg-orange-500 dark:bg-orange-600',
                            default => 'bg-sky-500 dark:bg-sky-600',
                        };
                @endphp
                <flux:timeline.item wire:key="cdr-{{ $call['uniqueid'] }}" align="start">
                    <flux:timeline.indicator :color="$call['indicator_color']">
                        <flux:icon.phone variant="micro" />
                    </flux:timeline.indicator>
                    <flux:timeline.content>
                        <div
                            class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/80 space-y-3 min-w-0">
                            @php
                                $headingParty = $call['heading_party'] ?? [
                                    'display' => $call['phone_display'],
                                    'avatar_url' => null,
                                    'has_avatar' => false,
                                    'avatar_name' => $call['phone_display'],
                                ];
                            @endphp
                            <div class="flex items-start gap-3">
                                <div class="relative shrink-0">
                                    @if (! empty($headingParty['has_avatar']) && ! empty($headingParty['avatar_url']))
                                        <flux:avatar src="{{ $headingParty['avatar_url'] }}" size="xs" />
                                    @else
                                        <flux:avatar name="{{ $headingParty['avatar_name'] }}" color="auto" size="xs" />
                                    @endif
                                    <span
                                        class="absolute -bottom-0.5 -end-0.5 flex size-5 items-center justify-center rounded-md {{ $cornerBadge }} text-white shadow-sm ring-2 ring-white dark:ring-zinc-800"
                                        title="{{ $call['disposition_label'] }}"
                                    >
                                        <flux:icon.phone variant="micro" class="size-2.5 opacity-95" />
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <flux:heading size="sm" class="truncate leading-snug font-semibold text-zinc-900 dark:text-zinc-50">
                                        {{ $call['phone_display'] }}
                                    </flux:heading>
                                    <div class="mt-1.5 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        <flux:icon.calendar variant="micro" class="size-3.5 shrink-0 opacity-80" />
                                        <span>{{ $call['jalali_datetime'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" :color="$call['badge_color']">
                                    {{ $call['disposition_label'] }}
                                </flux:badge>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('app.call_duration') }}
                                    <span dir="ltr" class="font-medium">{{ $call['duration_display'] }}</span>
                                </flux:text>
                            </div>
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('app.call_route') }}
                                <span class="inline-flex flex-wrap items-center gap-x-1 gap-y-1">
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
