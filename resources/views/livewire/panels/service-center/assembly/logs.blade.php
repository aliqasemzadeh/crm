<flux:modal name="panels.service-center.assembly.logs.modal" class="md:w-2/3" flyout position="right">
    @if ($assembly)
        <div class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg" level="2">{{ __('app.assembly_logs') }}</flux:heading>
                <flux:subheading size="md">
                    {{ $assembly->order?->code }} — {{ __('app.assembly_unit') }} #{{ $assembly->unit_number }}
                </flux:subheading>
            </div>

            <flux:separator variant="subtle" />

            <form wire:submit="addLog" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('app.type') }}</flux:label>
                    <flux:select wire:model="type" searchable>
                        @foreach ($this->typeOptions as $option)
                            <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.status') }}</flux:label>
                    <flux:select wire:model="status" searchable>
                        @foreach ($this->statusOptions as $option)
                            <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:textarea wire:model="description" rows="3" label="{{ __('app.log_description') }}" />
                <flux:error name="description" />

                <flux:button type="submit" variant="primary" color="orange" class="w-full">
                    {{ __('app.add_log') }}
                </flux:button>
            </form>

            <flux:separator variant="subtle" />

            <div class="max-h-96 space-y-2 overflow-y-auto">
                @forelse ($this->logsList as $log)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @php
                                $typeEnum = $log->type instanceof \App\Enums\AssemblyLogTypeEnum
                                    ? $log->type
                                    : \App\Enums\AssemblyLogTypeEnum::tryFrom((string) $log->type);
                            @endphp
                            @if ($typeEnum)
                                <flux:badge color="{{ $typeEnum->color() }}">{{ $typeEnum->label() }}</flux:badge>
                            @endif
                            @if ($log->status)
                                @php $statusEnum = \App\Enums\AssemblyStatusEnum::tryFromSafe($log->status); @endphp
                                <flux:badge color="{{ $statusEnum->color() }}">{{ $statusEnum->label() }}</flux:badge>
                            @endif
                        </div>
                        <flux:text class="font-medium">{{ $log->description }}</flux:text>
                        <div class="mt-1 flex items-center justify-between gap-2 text-sm text-zinc-500">
                            <span>{{ $log->user?->name ?: '-' }} • {{ $this->formatDate($log->created_at) }}</span>
                            <flux:button
                                size="xs"
                                variant="danger"
                                wire:click="deleteLog({{ $log->id }})"
                                wire:confirm="{{ __('app.are_you_sure') }}"
                            >
                                {{ __('app.delete') }}
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <flux:callout variant="subtle">{{ __('app.no_logs') }}</flux:callout>
                @endforelse
            </div>
        </div>
    @endif
</flux:modal>
