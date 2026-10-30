<flux:modal name="panels.sale.assembly.convert.modal" class="md:w-2/3 lg:w-1/2" flyout position="right">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="lg" level="2">{{ __('app.convert_to_assembly_order') }}</flux:heading>
            <flux:subheading size="md">{{ __('app.convert_to_assembly_order_description') }}</flux:subheading>
        </div>

        <flux:separator variant="subtle" />

        @if ($invoiceId)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <flux:callout icon="file-text" color="sky">
                    <flux:callout.heading>{{ __('app.invoice_number') }}</flux:callout.heading>
                    <flux:callout.text>{{ $invoiceNumber }}</flux:callout.text>
                </flux:callout>
                <flux:callout icon="user" color="violet">
                    <flux:callout.heading>{{ __('app.customer') }}</flux:callout.heading>
                    <flux:callout.text>{{ $customerName ?: '-' }}</flux:callout.text>
                </flux:callout>
            </div>

            <div class="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('app.assembly_ai_order') }}</flux:heading>
                <flux:text class="text-sm">{{ __('app.assembly_ai_order_help') }}</flux:text>
                <flux:textarea
                    wire:model="aiPrompt"
                    rows="3"
                    placeholder="{{ __('app.assembly_ai_prompt_placeholder') }}"
                />
                <flux:button
                    variant="primary"
                    color="violet"
                    icon="sparkles"
                    class="w-full"
                    wire:click="generateWithAi"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="generateWithAi">{{ __('app.assembly_ai_generate') }}</span>
                    <span wire:loading wire:target="generateWithAi">{{ __('app.assembly_ai_working') }}</span>
                </flux:button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('app.assembly_quantity') }}</flux:label>
                    <flux:input type="number" min="1" max="500" wire:model="quantity" />
                    <flux:error name="quantity" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.notes') }}</flux:label>
                    <flux:textarea wire:model="notes" rows="2" />
                    <flux:error name="notes" />
                </flux:field>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <flux:heading size="sm">{{ __('app.assembly_bom_parts') }}</flux:heading>
                        <flux:button type="button" size="sm" variant="filled" color="teal" icon="plus" wire:click="addPart">
                            {{ __('app.assembly_add_part') }}
                        </flux:button>
                    </div>
                    <flux:error name="parts" />

                    @forelse ($parts as $index => $part)
                        <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="sale-bom-{{ $index }}">
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading size="sm">#{{ $index + 1 }}</flux:heading>
                                <flux:tooltip content="{{ __('app.delete') }}">
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="primary"
                                        color="red"
                                        icon="trash"
                                        icon:variant="outline"
                                        wire:click="removePart({{ $index }})"
                                    />
                                </flux:tooltip>
                            </div>
                            <flux:input
                                wire:model="parts.{{ $index }}.title"
                                label="{{ __('app.assembly_part_title') }}"
                            />
                            <flux:error name="parts.{{ $index }}.title" />
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <flux:input
                                    type="number"
                                    min="1"
                                    wire:model="parts.{{ $index }}.quantity_per_unit"
                                    label="{{ __('app.assembly_quantity_per_unit') }}"
                                />
                                <flux:field>
                                    <flux:label>{{ __('app.assembly_requires_serial') }}</flux:label>
                                    <flux:switch wire:model="parts.{{ $index }}.requires_serial" />
                                </flux:field>
                            </div>
                        </div>
                    @empty
                        <flux:callout variant="subtle">{{ __('app.assembly_parts_required') }}</flux:callout>
                    @endforelse
                </div>

                <flux:button type="submit" variant="primary" color="orange" class="w-full">
                    {{ __('app.save') }}
                </flux:button>
            </form>
        @endif
    </div>
</flux:modal>
