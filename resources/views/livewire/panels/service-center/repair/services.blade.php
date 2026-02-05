<flux:modal name="panel.service-center.repair.services.modal" class="md:w-2/3" flyout position="right">
    @isset($this->repair)
        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg" level="2">
                        {{ __('app.repair_services') }}
                    </flux:heading>
                    <flux:subheading size="md">
                        {{ __('app.repair_services_description') }}
                    </flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            {{-- Form --}}
            <form wire:submit="addService" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('app.service_description') }}</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="{{ __('app.service_description') }}" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.service_price') }}</flux:label>
                    <flux:input.group>
                        <flux:input
                            wire:model="price"
                            type="text"
                            placeholder="0"
                            mask:dynamic="$money($input)"
                        />
                        <flux:input.group.suffix>{{ __('app.toman') }}</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:error name="price" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('app.service_type') }}</flux:label>
                    <flux:select wire:model="service_type" placeholder="{{ __('app.select_service_type') }}" size="sm">
                        <flux:select.option value="service">{{ __('app.service_type_service') }}</flux:select.option>
                        <flux:select.option value="part">{{ __('app.service_type_part') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="service_type" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('app.add_service') }}
                </flux:button>
            </form>

            <flux:separator variant="subtle" />

            {{-- Services List --}}
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="md" level="3">
                        {{ __('app.services_list') }}
                    </flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:text class="font-semibold">{{ __('app.total_price') }}:</flux:text>
                        <flux:text class="font-bold text-lg">
                            {{ number_format($this->totalPrice, 0) }} {{ __('app.toman') }}
                        </flux:text>
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    @if($this->servicesList->isEmpty())
                        <flux:callout variant="subtle">
                            {{ __('app.no_services') }}
                        </flux:callout>
                    @else
                        <div class="space-y-2">
                            @foreach($this->servicesList as $service)
                                <div class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700/50">
                                    <div class="flex-1">
                                        <flux:text class="font-medium">{{ $service->description }}</flux:text>
                                        <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            <flux:text>{{ __('app.technician') }}: {{ $service->technician->mobile ?? '-' }}</flux:text>
                                            <flux:text>•</flux:text>
                                            <flux:text>{{ $service->created_at->format('Y/m/d H:i') }}</flux:text>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <flux:text class="font-semibold">
                                            {{ number_format($service->price, 0) }} {{ __('app.toman') }}
                                        </flux:text>
                                        <flux:button
                                            size="xs"
                                            variant="danger"
                                            wire:click="deleteService({{ $service->id }})"
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
