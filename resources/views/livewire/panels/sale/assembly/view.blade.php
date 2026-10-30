<flux:modal name="panels.sale.assembly.view.modal" class="md:w-2/3 lg:w-1/2" flyout position="right">
    @if ($order)
        <div class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg" level="2">{{ __('app.assembly_order') }} {{ $order->code }}</flux:heading>
                <flux:subheading size="md">
                    {{ __('app.invoice_number') }}: {{ $order->invoice_number }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:text size="sm">{{ __('app.customer') }}</flux:text>
                    <flux:heading size="sm">{{ $order->customer_name ?: '-' }}</flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.status') }}</flux:text>
                    <flux:badge color="{{ $order->status_enum->color() }}">{{ $order->status_enum->label() }}</flux:badge>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.assembly_quantity') }}</flux:text>
                    <flux:heading size="sm">
                        {{ __('app.assembly_ready_of_total', ['ready' => $this->readyCount, 'total' => $order->quantity]) }}
                    </flux:heading>
                </div>
                <div>
                    <flux:text size="sm">{{ __('app.ordered_by') }}</flux:text>
                    <flux:heading size="sm">
                        {{ $order->orderedBy?->name ?: ($order->orderedBy?->mobile ?? '-') }}
                    </flux:heading>
                </div>
            </div>

            @if (filled($order->notes))
                <flux:callout>
                    <flux:callout.heading>{{ __('app.notes') }}</flux:callout.heading>
                    <flux:callout.text>{{ $order->notes }}</flux:callout.text>
                </flux:callout>
            @endif

            <div>
                <flux:heading size="sm" class="mb-2">{{ __('app.assembly_bom_parts') }}</flux:heading>
                <div class="space-y-2">
                    @foreach ($order->parts as $part)
                        <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-2">
                                <flux:text class="font-medium">{{ $part->title }}</flux:text>
                                <flux:badge size="sm" color="zinc">x{{ $part->quantity_per_unit }}</flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <flux:heading size="sm" class="mb-2">{{ __('app.assemblies') }}</flux:heading>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach ($order->assemblies as $assembly)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <flux:heading size="sm">
                                    {{ __('app.assembly_unit') }} #{{ $assembly->unit_number }}
                                </flux:heading>
                                <flux:badge color="{{ $assembly->status->color() }}">{{ $assembly->status->label() }}</flux:badge>
                            </div>
                            <div class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 space-y-1">
                                <div>{{ __('app.assembly_system_serial') }}: {{ $assembly->system_serial ?: '-' }}</div>
                                <div>
                                    {{ __('app.assembled_by') }}:
                                    {{ $assembly->assembledBy?->name ?: '-' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</flux:modal>
