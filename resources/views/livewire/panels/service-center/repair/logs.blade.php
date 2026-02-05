<flux:modal name="panel.service-center.repair.logs.modal" class="md:w-2/3" flyout position="right">
    @isset($this->repair)
        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg" level="2">
                        {{ __('app.repair_logs') }}
                    </flux:heading>
                    <flux:subheading size="md">
                        {{ __('app.repair_logs_description') }}
                    </flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            {{-- Form --}}
            <form wire:submit="addLog" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('app.log_status') }}</flux:label>
                    <flux:select wire:model="status" placeholder="{{ __('app.select_status') }}">
                        @foreach($this->statusOptions as $option)
                            <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.log_description') }}</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="{{ __('app.log_description') }}" />
                    <flux:error name="description" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('app.add_log') }}
                </flux:button>
            </form>

            <flux:separator variant="subtle" />

            {{-- Logs List --}}
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="md" level="3">
                        {{ __('app.logs_list') }}
                    </flux:heading>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    @if($this->logsList->isEmpty())
                        <flux:callout variant="subtle">
                            {{ __('app.no_logs') }}
                        </flux:callout>
                    @else
                        <div class="space-y-2">
                            @foreach($this->logsList as $log)
                                <div class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700/50">
                                    <div class="flex-1">
                                        <div class="mb-2">
                                            @php
                                                $statusEnum = \App\Enums\StatusEnum::tryFromSafe($log->status);
                                            @endphp
                                            <flux:badge variant="solid" color="{{ $statusEnum->color() }}">
                                                {{ $statusEnum->label() }}
                                            </flux:badge>
                                        </div>
                                        <flux:text class="font-medium">{{ $log->description }}</flux:text>
                                        <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            <flux:text>{{ __('app.technician') }}: {{ $log->technician->mobile ?? '-' }}</flux:text>
                                            <flux:text>•</flux:text>
                                            <flux:text>{{ $log->created_at->format('Y/m/d H:i') }}</flux:text>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
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
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endisset
</flux:modal>
