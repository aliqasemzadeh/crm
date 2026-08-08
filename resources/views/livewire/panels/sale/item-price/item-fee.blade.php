<div class="space-y-4">

    @can('sales_item_fee')
    <flux:card class="m-3">
        <form wire:submit="save">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input
                        required
                        wire:model="fee"
                        label="{{ __('app.price_announcement') }}"
                        mask:dynamic="$money($input, '.', ',', 0)"
                        :icon-trailing="$feeSaved ? 'check' : ''"
                        :class="$feeSaved ? '!border-green-500' : ''"
                    />
                </div>
                <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
            </div>
        </form>
    </flux:card>
    @endcan
    @can('sales_item_site_edit')
    @foreach($prices as $index => $price)
        <flux:card class="m-3">
            <form wire:submit="saveSite({{ $index }})">
                <div class="mb-2 text-sm font-bold flex items-center gap-2">
                    @if($price['color_code'])
                        <span class="w-4 h-4 rounded-full border border-gray-200" style="background-color: {{ $price['color_code'] }}"></span>
                    @endif
                    {{ $price['color'] }} - {{ $price['guarantee'] }}
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <flux:input
                            wire:model="prices.{{ $index }}.fee"
                            label="{{ __('app.site_price') }}"
                            mask:dynamic="$money($input, '.', ',', 0)"
                            :icon-trailing="$price['saved'] ? 'check' : ''"
                            :class="$price['saved'] ? '!border-green-500' : ''"
                        />
                    </div>
                    <div class="w-24">
                        <flux:input wire:model="prices.{{ $index }}.stock" label="{{ __('app.site_stock') }}" type="number" />
                    </div>
                    <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endforeach
    @endcan
</div>
