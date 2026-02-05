<x-slot name="title">
    {{ __('app.repairs') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.repairs') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.repairs_description') }}</flux:subheading>
            </div>
            @can('service_center_repair_create')
            <flux:modal.trigger name="panel.service-center.repair.admission.modal">
                <flux:button variant="primary">{{ __('app.admission') }}</flux:button>
            </flux:modal.trigger>
            @endcan
        </div>

        <flux:separator variant="subtle" />
    </div>

    @can('service_center_repair_create')
    <livewire:panel.service-center.repair.create />
    @endcan
    @can('service_center_repair_view')
    <livewire:panel.service-center.repair.problem />
    <livewire:panel.service-center.repair.view />
    @endcan
    @can('service_center_repair_services')
    <livewire:panel.service-center.repair.services />
    @endcan
    @can('service_center_repair_logs')
    <livewire:panel.service-center.repair.logs />
    @endcan
    @can('service_center_repair_edit')
        <livewire:panel.service-center.repair.edit />
    @endcan

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        @foreach ($this->stats as $stat)
            <div
                wire:click="$set('statusFilter', '{{ $stat['status'] }}')"
                class="relative cursor-pointer rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800 shadow-sm hover:border-{{ $stat['color'] }}-500 transition-colors {{ $statusFilter === $stat['status'] ? 'ring-2 ring-'.$stat['color'].'-500' : '' }}"
            >
                <flux:subheading size="sm" class="uppercase tracking-wider">{{ $stat['label'] }}</flux:subheading>
                <div class="flex items-end justify-between mt-2">
                    <flux:heading size="xl" class="leading-none">{{ $stat['value'] }}</flux:heading>
                    <div class="p-2 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30">
                        <flux:icon icon="wrench-screwdriver" variant="micro" class="text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="relative">
        <div wire:loading.flex class="absolute inset-0 z-10 items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <flux:skeleton.group animate="shimmer" class="w-full h-full p-4">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('app.id') }}</flux:table.column>
                        <flux:table.column>{{ __('app.owner_name') }}</flux:table.column>
                        <flux:table.column>{{ __('app.owner_mobile') }}</flux:table.column>
                        <flux:table.column>{{ __('app.status') }}</flux:table.column>
                        <flux:table.column>{{ __('app.device_type') }}</flux:table.column>
                        <flux:table.column>{{ __('app.device_serial_number') }}</flux:table.column>
                        <flux:table.column>{{ __('app.date') }}</flux:table.column>
                        <flux:table.column>{{ __('app.options') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach (range(1, 3) as $i)
                            <flux:table.row>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                                <flux:table.cell><flux:skeleton.line /></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:skeleton.group>
        </div>

        <flux:table :paginate="$this->repairs">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column colspan="8" class="bg-white dark:bg-zinc-900">
                    <div class="flex flex-col gap-2 pe-2 items-end">
                        <div class="flex gap-2 w-full">
                            <div class="flex-1">
                                <flux:input
                                    size="sm"
                                    placeholder="{{ __('app.search_placeholder') }}"
                                    wire:model.live="search"
                                />
                            </div>
                            <div class="w-48">
                                <flux:select
                                    size="sm"
                                    placeholder="{{ __('app.filter_status') }}"
                                    wire:model.live="statusFilter"
                                >
                                    <option value="">{{ __('app.all_statuses') }}</option>
                                    @foreach($this->statusOptions as $status)
                                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                                    @endforeach
                                </flux:select>
                            </div>
                            @if($statusFilter)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="clearStatusFilter"
                                >
                                    {{ __('app.clear') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </flux:table.column>
            </flux:table.columns>
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column class="bg-white dark:bg-zinc-900">
                        <span>{{ __('app.id') }}</span>
                </flux:table.column>
                <flux:table.column class="bg-white dark:bg-zinc-900">
                    <span>{{ __('app.owner_name') }}</span>
                </flux:table.column>
                <flux:table.column class="bg-white dark:bg-zinc-900">
                    <span>{{ __('app.owner_mobile') }}</span>
                </flux:table.column>
                <flux:table.column class="bg-white dark:bg-zinc-900">
                    <span>{{ __('app.status') }}</span>
                </flux:table.column>
                <flux:table.column class="bg-white dark:bg-zinc-900">
                    <span>{{ __('app.device_type') }}</span>
                </flux:table.column>
                <flux:table.column class="bg-white dark:bg-zinc-900">
                    <span>{{ __('app.device_serial_number') }}</span>
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.date') }}</flux:table.column>
                <flux:table.column class="bg-white dark:bg-zinc-900">
                    <span>{{ __('app.options') }}</span>
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->repairs as $repair)
                    <flux:table.row :key="$repair->id">
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $repair->admission_code }}
                        </flux:table.cell>
                        <flux:table.cell class="flex flex-col">
                            {{ $repair->owner_name }}
                            @if($repair->owner_organization)
                                <br />
                                {{ $repair->owner_organization }}
                            @endif


                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $repair->owner_mobile }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusEnum = \App\Enums\StatusEnum::tryFromSafe($repair->status);
                            @endphp
                            <flux:badge variant="solid" color="{{ $statusEnum->color() }}">
                                {{ $statusEnum->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $repair->device_type }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $repair->device_serial_number }}
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ \Morilog\Jalali\Jalalian::fromCarbon($repair->created_at)->format('%Y-%m-%d %H:%M') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @can('service_center_repair_view')
                            <flux:button
                                size="xs"
                                variant="primary"
                                wire:click="$dispatch('panel.service-center.repair.view.assign-data', { id: {{ $repair->id }} })"
                            >
                                {{ __('app.view') }}
                            </flux:button>
                            <flux:button
                                size="xs"
                                variant="danger"
                                wire:click="$dispatch('panel.service-center.repair.problem.assign-data', { id: {{ $repair->id }} })"
                            >
                                {{ __('app.problem') }}
                            </flux:button>
                            @endcan
                            @can('service_center_repair_services')
                            <flux:button size="xs" variant="primary" color="orange"
                                         wire:click="$dispatch('panel.service-center.repair.services.assign-data', { id: {{ $repair->id }} })"
                            >
                                {{ __('app.services') }}
                            </flux:button>
                            @endcan
                            @can('service_center_repair_logs')
                            <flux:button size="xs" variant="primary" color="lime"
                                         wire:click="$dispatch('panel.service-center.repair.logs.assign-data', { id: {{ $repair->id }} })"
                            >
                                {{ __('app.logs') }}
                            </flux:button>
                            @endcan

                                @can('service_center_repair_edit')
                                    <flux:button size="xs" variant="primary" color="blue"
                                                 wire:click="$dispatch('panel.service-center.repair.edit.assign-data', { id: {{ $repair->id }} })"
                                    >
                                        {{ __('app.edit') }}
                                    </flux:button>
                                @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
