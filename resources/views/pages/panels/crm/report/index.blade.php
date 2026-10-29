<?php

use App\Jobs\Crm\AnalyzeDailyCallReportJob;
use App\Models\Crm\CallReport;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.crm')] class extends Component
{
    #[Url]
    public string $reportDate = '';

    public bool $autoQueued = false;

    public function mount(): void
    {
        $this->authorize('crm_report_index');

        if ($this->reportDate === '') {
            $this->reportDate = Jalalian::now()->subDays(1)->format('Y/m/d');
        }

        $this->ensureReportForSelectedDate();
    }

    public function updatedReportDate(): void
    {
        $this->autoQueued = false;
        $this->ensureReportForSelectedDate();
    }

    public function runAnalysis(bool $force = false): void
    {
        $gregorian = $this->gregorianDate();
        if ($gregorian === null) {
            Flux::toast(__('app.call_report_invalid_date'), variant: 'danger');

            return;
        }

        $existing = CallReport::query()->whereDate('report_date', $gregorian)->first();

        if ($existing?->isProcessing() && ! $force) {
            Flux::toast(__('app.call_report_already_running'));

            return;
        }

        if ($existing && $force) {
            $existing->update([
                'status' => 'pending',
                'error' => null,
            ]);
        } elseif (! $existing) {
            CallReport::query()->create([
                'report_date' => $gregorian,
                'status' => 'pending',
                'top_count' => (int) config('crm.call_report.top_count', 3),
            ]);
        }

        AnalyzeDailyCallReportJob::dispatch($gregorian);
        $this->autoQueued = true;
        unset($this->report);

        Flux::toast(__('app.call_report_queued'));
    }

    public function refreshReport(): void
    {
        unset($this->report);
    }

    #[Computed]
    public function report(): ?CallReport
    {
        $gregorian = $this->gregorianDate();
        if ($gregorian === null) {
            return null;
        }

        return CallReport::query()
            ->with('parties')
            ->whereDate('report_date', $gregorian)
            ->first();
    }

    private function ensureReportForSelectedDate(): void
    {
        $gregorian = $this->gregorianDate();
        if ($gregorian === null || $this->autoQueued) {
            return;
        }

        $report = CallReport::query()->whereDate('report_date', $gregorian)->first();
        if ($report !== null) {
            return;
        }

        try {
            CallReport::query()->create([
                'report_date' => $gregorian,
                'status' => 'pending',
                'top_count' => (int) config('crm.call_report.top_count', 3),
            ]);
            AnalyzeDailyCallReportJob::dispatch($gregorian);
            $this->autoQueued = true;
            unset($this->report);
            Flux::toast(__('app.call_report_auto_queued'));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function gregorianDate(): ?string
    {
        if (trim($this->reportDate) === '') {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y/m/d', $this->reportDate)->toCarbon()->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function outcomeLabel(string $outcome): string
    {
        return __('app.call_report_outcome.'.$outcome);
    }

    public function outcomeColor(string $outcome): string
    {
        return match ($outcome) {
            'sold' => 'green',
            'no_invoice' => 'amber',
            'unknown_party' => 'rose',
            'no_audio' => 'zinc',
            'failed' => 'red',
            default => 'sky',
        };
    }
};
?>

<x-slot name="title">
    {{ __('app.call_report_title') }}
</x-slot>

<div
    class="space-y-6"
    @if ($this->report?->isProcessing())
        wire:poll.5s="refreshReport"
    @endif
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('app.call_report_title') }}</flux:heading>
            <flux:subheading size="lg" class="mb-2">{{ __('app.call_report_description') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <flux:tooltip content="{{ __('app.refresh') }}">
                <flux:button
                    size="sm"
                    variant="primary"
                    color="sky"
                    icon="refresh-cw"
                    icon:variant="outline"
                    wire:click="refreshReport"
                />
            </flux:tooltip>
            <flux:tooltip content="{{ __('app.call_report_rerun') }}">
                <flux:button
                    size="sm"
                    variant="primary"
                    color="orange"
                    icon="play"
                    icon:variant="outline"
                    wire:click="runAnalysis(true)"
                    wire:confirm="{{ __('app.confirm_action') }}"
                />
            </flux:tooltip>
        </div>
    </div>

    <flux:separator variant="subtle" />

    <flux:card class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-date-select wire:model.live="reportDate" :label="__('app.call_report_day')" />
            <div class="flex flex-col justify-end gap-1 md:col-span-2">
                @if ($this->report)
                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $statusColor = match ($this->report->status) {
                                'completed' => 'green',
                                'failed' => 'red',
                                'processing' => 'amber',
                                default => 'sky',
                            };
                        @endphp
                        <flux:badge size="sm" :color="$statusColor">
                            {{ __('app.call_report_status.'.$this->report->status) }}
                        </flux:badge>
                        <span class="text-sm text-zinc-500">
                            {{ __('app.call_report_top_count_label', ['count' => $this->report->top_count]) }}
                        </span>
                    </div>
                    @if (filled($this->report->summary))
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $this->report->summary }}</p>
                    @endif
                    @if (filled($this->report->error))
                        <p class="text-sm text-red-600">{{ $this->report->error }}</p>
                    @endif
                @else
                    <p class="text-sm text-zinc-500">{{ __('app.call_report_loading') }}</p>
                @endif
            </div>
        </div>
    </flux:card>

    @if ($this->report?->isProcessing())
        <flux:card class="space-y-2">
            <flux:heading size="lg">{{ __('app.call_report_processing_title') }}</flux:heading>
            <flux:subheading>{{ __('app.call_report_processing_hint') }}</flux:subheading>
        </flux:card>
    @endif

    @if ($this->report?->isCompleted())
        <div class="space-y-4">
            @forelse ($this->report->parties as $party)
                <flux:card wire:key="call-report-party-{{ $party->id }}" class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <flux:heading size="lg">
                                #{{ $party->rank }} —
                                {{ $party->party_name ?: __('app.unknown_call_party') }}
                            </flux:heading>
                            <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                                <span dir="ltr" class="font-mono">{{ $party->phone }}</span>
                                <span>•</span>
                                <span>{{ __('app.call_report_call_count', ['count' => $party->call_count]) }}</span>
                            </div>
                        </div>
                        <flux:badge size="sm" :color="$this->outcomeColor($party->outcome)">
                            {{ $this->outcomeLabel($party->outcome) }}
                        </flux:badge>
                    </div>

                    @if (filled($party->identity_note))
                        <flux:callout variant="danger" icon="triangle-alert">
                            {{ $party->identity_note }}
                        </flux:callout>
                    @endif

                    @if ($party->has_invoice_48h)
                        <flux:callout variant="success" icon="circle-check">
                            {{ __('app.call_report_sold_message') }}
                            @if (filled($party->invoice_summary))
                                <div class="mt-1 text-sm opacity-90">{{ $party->invoice_summary }}</div>
                            @endif
                        </flux:callout>
                    @elseif ($party->party_id)
                        <flux:callout variant="warning" icon="info">
                            {{ __('app.call_report_no_invoice_message') }}
                        </flux:callout>
                    @endif

                    <div class="space-y-3">
                        <flux:heading size="sm">{{ __('app.call_report_transcripts') }}</flux:heading>
                        @if (! empty($party->transcripts))
                            <div class="space-y-3">
                                @foreach ($party->transcripts as $index => $transcript)
                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                                        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                            <flux:badge size="sm" color="sky">
                                                {{ __('app.call_report_transcript_n', ['n' => $index + 1]) }}
                                            </flux:badge>
                                            <span dir="ltr" class="font-mono">{{ $transcript['calldate'] ?? '' }}</span>
                                            <span>{{ __('app.call_duration') }} {{ $transcript['billsec'] ?? 0 }}s</span>
                                        </div>
                                        <p class="whitespace-pre-wrap text-sm leading-8 text-zinc-800 dark:text-zinc-100">{{ $transcript['text'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <flux:callout icon="info" variant="secondary">
                                {{ __('app.call_report_no_transcripts') }}
                            </flux:callout>
                        @endif
                    </div>

                    @php($analysis = $party->analysis ?? [])
                    @if (! empty($analysis['wanted_summary']) || ! empty($analysis['key_points']) || ! empty($analysis['follow_up_suggestion']))
                        <flux:separator variant="subtle" />
                        <div class="space-y-4">
                            <flux:heading size="sm">{{ __('app.call_report_ai_analysis') }}</flux:heading>

                            @if (! empty($analysis['wanted_summary']))
                                <div class="space-y-2">
                                    <flux:text class="font-medium">{{ __('app.call_report_wanted_summary') }}</flux:text>
                                    <p class="text-sm leading-7 text-zinc-700 dark:text-zinc-200">{{ $analysis['wanted_summary'] }}</p>
                                </div>
                            @endif

                            @if (! empty($analysis['key_points']) && is_array($analysis['key_points']))
                                <div class="space-y-2">
                                    <flux:text class="font-medium">{{ __('app.call_report_key_points') }}</flux:text>
                                    <ul class="list-disc space-y-1 pe-5 text-sm text-zinc-700 dark:text-zinc-200">
                                        @foreach ($analysis['key_points'] as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! empty($analysis['follow_up_suggestion']))
                                <div class="space-y-1">
                                    <flux:text class="font-medium">{{ __('app.call_report_follow_up') }}</flux:text>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $analysis['follow_up_suggestion'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </flux:card>
            @empty
                <flux:card>
                    <p class="py-6 text-center text-zinc-500">{{ __('app.call_report_no_parties') }}</p>
                </flux:card>
            @endforelse
        </div>
    @endif
</div>
