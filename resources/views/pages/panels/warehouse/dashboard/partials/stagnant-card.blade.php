@php
    $title = $title ?? '';
    $rows = $rows ?? [];
@endphp

<flux:card class="border-t-4 border-rose-500">
    <flux:heading size="lg" class="mb-1">{{ $title }}</flux:heading>
    <flux:text class="mb-4 text-sm text-zinc-500">{{ __('app.warehouse_stagnant_items_hint') }}</flux:text>

    @if(count($rows) === 0)
        <flux:text class="text-zinc-500">{{ __('app.no_data') }}</flux:text>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-start text-zinc-500 dark:border-zinc-700">
                        <th class="py-2 pe-2 font-medium">#</th>
                        <th class="py-2 pe-2 font-medium">{{ __('app.title') }}</th>
                        <th class="py-2 pe-2 font-medium tabular-nums">{{ __('app.stock') }}</th>
                        <th class="py-2 font-medium">{{ __('app.last_sale') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="stagnant-{{ $row['id'] }}">
                            <td class="py-2 pe-2 tabular-nums text-zinc-500">{{ $index + 1 }}</td>
                            <td class="py-2 pe-2">
                                <div class="font-medium">{{ $row['title'] ?: '-' }}</div>
                                @if(!empty($row['code']))
                                    <div class="text-xs text-zinc-500">{{ $row['code'] }}</div>
                                @endif
                            </td>
                            <td class="py-2 pe-2 tabular-nums">{{ number_format($row['quantity']) }}</td>
                            <td class="py-2 text-zinc-500">
                                {{ $row['last_sale_at'] ?: __('app.never') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</flux:card>
