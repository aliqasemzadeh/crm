<x-slot name="title">
    {{ __('app.accounting') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.administrator') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.administrator_description') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button wire:click="reload" icon="arrow-path" size="sm" variant="subtle">{{ __('app.reload') }}</flux:button>
                <flux:select wire:model.live="fiscalYearRef" class="w-48">
                    @foreach($this->fiscalYears as $year)
                        <flux:select.option value="{{ $year->FiscalYearID }}">{{ $year->Year }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-6">
        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.inventory_rial_balance') }}</flux:subheading>

            <flux:heading size="xl" class="mb-2">{{ number_format($this->inventory) }}</flux:heading>

            <div class="flex items-center gap-1 font-medium text-sm">

            </div>

            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>

        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.total_items_count') }}</flux:subheading>

            <flux:heading size="xl" class="mb-2">{{ number_format($this->totalItemsCount) }}</flux:heading>

            <div class="flex items-center gap-1 font-medium text-sm">

            </div>

            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>

        <div class="relative rounded-lg px-6 py-4 bg-zinc-50 dark:bg-zinc-700">
            <flux:subheading>{{ __('app.unlinked_items_count') }}</flux:subheading>

            <flux:heading size="xl" class="mb-2">{{ number_format($this->unlinkedItemsCount) }}</flux:heading>

            <div class="flex items-center gap-1 font-medium text-sm">

            </div>

            <div class="absolute top-0 right-0 pr-2 pt-2">
                <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
            </div>
        </div>
    </div>

</div>
