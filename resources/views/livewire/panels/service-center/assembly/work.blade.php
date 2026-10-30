<flux:modal name="panels.service-center.assembly.work.modal" class="md:w-2/3 lg:w-1/2" flyout position="right">
    @if ($assembly)
        <div class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg" level="2">
                    {{ __('app.assembly_work') }} — {{ $assembly->order?->code }} #{{ $assembly->unit_number }}
                </flux:heading>
                <flux:subheading size="md">{{ __('app.assembly_work_description') }}</flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('app.assembly_ai_serial') }}</flux:heading>
                <flux:text class="text-sm">{{ __('app.assembly_ai_serial_help') }}</flux:text>
                <flux:textarea wire:model="aiPrompt" rows="2" placeholder="{{ __('app.assembly_ai_prompt_placeholder') }}" />
                <flux:button
                    variant="primary"
                    color="violet"
                    icon="sparkles"
                    class="w-full"
                    wire:click="generateWithAi"
                >
                    <span wire:loading.remove wire:target="generateWithAi">{{ __('app.assembly_ai_generate') }}</span>
                    <span wire:loading wire:target="generateWithAi">{{ __('app.assembly_ai_working') }}</span>
                </flux:button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <flux:input wire:model="system_serial" label="{{ __('app.assembly_system_serial') }}" />
                    <flux:field>
                        <flux:label>{{ __('app.assembled_by') }}</flux:label>
                        <flux:select wire:model="assembled_by_user_id" searchable placeholder="{{ __('app.assembly_select_assembler') }}">
                            <flux:select.option value="">{{ __('app.assembly_select_assembler') }}</flux:select.option>
                            @foreach ($this->users as $user)
                                <flux:select.option value="{{ $user->id }}">
                                    {{ $user->name ?: $user->mobile }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('app.status') }}</flux:label>
                    <flux:select wire:model="status" searchable>
                        @foreach ($this->statusOptions as $option)
                            <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:textarea wire:model="notes" label="{{ __('app.notes') }}" rows="2" />

                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('app.assembly_bom_parts') }}</flux:heading>
                    @foreach ($parts as $index => $part)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="sc-part-{{ $part['id'] }}">
                            <flux:heading size="sm" class="mb-2">{{ $part['title'] }}</flux:heading>
                            <flux:input
                                wire:model="parts.{{ $index }}.serial_number"
                                label="{{ __('app.assembly_part_serial') }}"
                            />
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <flux:button type="button" variant="primary" color="amber" class="w-full" wire:click="startAssembly">
                        {{ __('app.assembly_start') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" color="green" class="w-full" wire:click="completeAssembly">
                        {{ __('app.assembly_complete') }}
                    </flux:button>
                </div>

                <flux:button type="submit" variant="primary" color="orange" class="w-full">
                    {{ __('app.save') }}
                </flux:button>
            </form>
        </div>
    @endif
</flux:modal>
