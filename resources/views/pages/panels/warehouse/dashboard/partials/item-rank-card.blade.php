@php
    $title = $title ?? '';
    $rows = $rows ?? [];
    $accent = $accent ?? 'sky';
    $showProfit = $showProfit ?? true;

    $borderClass = match ($accent) {
        'amber' => 'border-amber-500',
        'indigo' => 'border-indigo-500',
        'cyan' => 'border-cyan-500',
        'rose' => 'border-rose-500',
        'teal' => 'border-teal-500',
        'emerald' => 'border-emerald-500',
        default => 'border-sky-500',
    };
@endphp

<flux:card class="border-t-4 {{ $borderClass }}">
    <flux:heading size="lg" class="mb-4">{{ $title }}</flux:heading>

    @if(count($rows) === 0)
        <flux:text class="text-zinc-500">{{ __('app.no_data') }}</flux:text>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-start text-zinc-500 dark:border-zinc-700">
                        <th class="py-2 pe-2 font-medium">#</th>
                        <th class="py-2 pe-2 font-medium">{{ __('app.title') }}</th>
                        <th class="py-2 pe-2 font-medium tabular-nums">{{ __('app.quantity') }}</th>
                        @if($showProfit)
                            <th class="py-2 font-medium tabular-nums">{{ __('app.sales_expert_profit') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="item-rank-{{ $accent }}-{{ $row['id'] }}">
                            <td class="py-2 pe-2 tabular-nums text-zinc-500">{{ $index + 1 }}</td>
                            <td class="py-2 pe-2">
                                <div class="font-medium">{{ $row['title'] ?: '-' }}</div>
                                @if(!empty($row['code']))
                                    <div class="text-xs text-zinc-500">{{ $row['code'] }}</div>
                                @endif
                            </td>
                            <td class="py-2 pe-2 tabular-nums">{{ number_format($row['quantity']) }}</td>
                            @if($showProfit)
                                <td class="py-2 tabular-nums whitespace-nowrap">
                                    {{ number_format($row['profit'] ?? 0) }}
                                    <span class="text-xs text-zinc-500">{{ __('app.rial') }}</span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</flux:card>
