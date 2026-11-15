<?php

use App\Settings\InvoiceSettings;
use App\Support\Sepidar\StockOptions;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    public int $default_stock_ref = 2;

    public bool $show_stock_selector = false;

    public function mount(InvoiceSettings $settings): void
    {
        $this->authorize('administrator_setting_option_index');

        $this->default_stock_ref = $settings->resolvedDefaultStockRef();
        $this->show_stock_selector = $settings->show_stock_selector;
    }

    public function save(InvoiceSettings $settings): void
    {
        $this->authorize('administrator_setting_option_index');

        $validated = $this->validate([
            'default_stock_ref' => ['required', 'integer', 'min:1'],
            'show_stock_selector' => ['required', 'boolean'],
        ], [], [
            'default_stock_ref' => __('app.default_warehouse'),
            'show_stock_selector' => __('app.show_warehouse_selector'),
        ]);

        $settings->default_stock_ref = (int) $validated['default_stock_ref'];
        $settings->show_stock_selector = (bool) $validated['show_stock_selector'];
        $settings->save();

        Flux::toast(__('app.options_saved'));
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function stocks(): Collection
    {
        return StockOptions::labels($this->default_stock_ref);
    }
};
?>

<x-slot name="title">
    {{ __('app.options') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full space-y-6">
        <flux:heading size="xl" level="1">{{ __('app.options') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.options_description') }}</flux:subheading>
        <flux:separator variant="subtle" />

        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.invoice_options') }}</flux:heading>
                <flux:subheading>{{ __('app.invoice_options_description') }}</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <flux:select
                        wire:model="default_stock_ref"
                        searchable
                        label="{{ __('app.default_warehouse') }}"
                        placeholder="{{ __('app.default_warehouse') }}"
                    >
                        @foreach ($this->stocks as $stockId => $stockTitle)
                            <flux:select.option value="{{ $stockId }}" wire:key="option-stock-{{ $stockId }}">
                                {{ $stockTitle }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="space-y-2">
                        <flux:switch
                            wire:model="show_stock_selector"
                            label="{{ __('app.show_warehouse_selector') }}"
                        />
                        <flux:text class="text-sm text-zinc-500">
                            {{ __('app.show_warehouse_selector_description') }}
                        </flux:text>
                    </div>
                </div>

                <flux:button type="submit" variant="primary" color="orange" icon="save" class="w-full">
                    {{ __('app.save') }}
                </flux:button>
            </form>
        </flux:card>
    </div>
</div>
