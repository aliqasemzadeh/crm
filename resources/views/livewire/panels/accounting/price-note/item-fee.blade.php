<div class="space-y-4">

    @can('accounting_price_note_edit')
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
    @endcan
    @can('accounting_price_note_site_edit')
    @if($productPriceId)
        <hr class="border-gray-200 dark:border-white/10" />

        <form wire:submit="saveSite">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input
                        wire:model="siteFee"
                        label="{{ __('app.site_price') }}"
                        mask:dynamic="$money($input, '.', ',', 0)"
                        :icon-trailing="$siteSaved ? 'check' : ''"
                        :class="$siteSaved ? '!border-green-500' : ''"
                    />
                </div>
                <div class="w-24">
                    <flux:input wire:model="siteStock" label="{{ __('app.site_stock') }}" type="number" />
                </div>
                <flux:button type="submit" variant="primary">{{ __('app.save') }}</flux:button>

            </div>
        </form>
    @endif
    @endcan
</div>
