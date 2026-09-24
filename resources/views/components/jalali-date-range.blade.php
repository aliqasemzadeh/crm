@props([
    'label' => null,
    'placeholder' => null,
    'min' => null,
    'max' => null,
    'startModel' => null,
    'endModel' => null,
    'start' => null,
    'end' => null,
])

@php
    $startModel ??= $attributes->get('wire:model.start');
    $endModel ??= $attributes->get('wire:model.end');
    $placeholder ??= __('app.date_range_placeholder');
    $label ??= __('app.date_range');
@endphp

<div
    class="w-full"
    x-data="window.jalaliDateRange({
        start: @js($start),
        end: @js($end),
        startModel: @js($startModel),
        endModel: @js($endModel),
        min: @js($min),
        max: @js($max),
        placeholder: @js($placeholder),
        fromToLabel: @js(__('app.date_range_from_to')),
    })"
    x-cloak
>
    <flux:field>
        @if ($label)
            <flux:label>{{ $label }}</flux:label>
        @endif

        <div class="relative w-full">
            <flux:input
                readonly
                @click="showDatepicker = !showDatepicker"
                @keydown.escape="showDatepicker = false"
                x-bind:value="displayValue"
                :placeholder="$placeholder"
                class="cursor-pointer"
            >
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                        <line x1="16" x2="16" y1="2" y2="6"></line>
                        <line x1="8" x2="8" y1="2" y2="6"></line>
                        <line x1="3" x2="21" y1="10" y2="10"></line>
                    </svg>
                </x-slot>

                <x-slot name="suffix">
                    <template x-if="start || end || draftStart">
                        <button
                            type="button"
                            x-on:click.stop="clearRange()"
                            class="h-4 w-4 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            :title="@js(__('app.clear_date_range'))"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </template>
                </x-slot>
            </flux:input>

            <div
                class="absolute z-50 mt-2 w-[300px] rounded-md border border-zinc-200 bg-white p-3 text-zinc-950 shadow-md outline-none dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                x-show="showDatepicker"
                @click.away="showDatepicker = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                style="display: none;"
            >
                <div class="flex items-center justify-between pb-4">
                    <button type="button" @click="previousMonth()" :disabled="!canGoPrevious()" class="h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100 transition-opacity disabled:opacity-20 disabled:pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m9 18 6-6-6-6"/></svg>
                    </button>

                    <div class="text-sm font-medium">
                        <span x-text="MONTH_NAMES[month - 1]"></span>
                        <span x-text="year"></span>
                    </div>

                    <button type="button" @click="nextMonth()" :disabled="!canGoNext()" class="h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100 transition-opacity disabled:opacity-20 disabled:pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <template x-for="day in DAYS" :key="day">
                        <div class="text-zinc-500 dark:text-zinc-400 rounded-md w-9 h-9 flex items-center justify-center text-[0.8rem] font-normal" x-text="day"></div>
                    </template>

                    <template x-for="blankday in blankdays" :key="'b'+blankday">
                        <div class="w-9 h-9"></div>
                    </template>

                    <template x-for="date in no_of_days" :key="date">
                        <button
                            type="button"
                            @click="selectDay(date)"
                            :disabled="isDisabled(date)"
                            class="h-9 w-9 p-0 font-normal transition-colors text-sm flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 disabled:opacity-30 disabled:pointer-events-none"
                            :class="{
                                'rounded-md bg-zinc-900 text-zinc-50 dark:bg-zinc-50 dark:text-zinc-900': isRangeEdge(date),
                                'rounded-none bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50': isInRange(date) && !isRangeEdge(date),
                                'rounded-s-md': isStart(date) && !isEnd(date),
                                'rounded-e-md': isEnd(date) && !isStart(date),
                                'rounded-md bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50': isToday(date) && !isInRange(date) && !isRangeEdge(date),
                                'hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md': !isInRange(date) && !isRangeEdge(date) && !isDisabled(date),
                            }"
                            x-text="date"
                        ></button>
                    </template>
                </div>

                <p class="mt-3 text-xs text-zinc-500 text-center" x-text="hintText"></p>
            </div>
        </div>
    </flux:field>
</div>
