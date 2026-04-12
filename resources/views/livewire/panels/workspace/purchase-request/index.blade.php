<x-slot name="title">
    {{ __('app.purchase_requests') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.purchase_requests') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.purchase_requests_description') }}</flux:subheading>
            </div>
            <flux:modal.trigger name="panels.workspace.purchase-request.create.modal">
                <flux:button variant="primary">{{ __('app.create_purchase_request') }}</flux:button>
            </flux:modal.trigger>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <livewire:panels.workspace.purchase-request.create />
    <livewire:panels.workspace.purchase-request.view />

    <flux:table :paginate="$requests">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.id') }}</flux:table.column>
            <flux:table.column>{{ __('app.user') }}</flux:table.column>
            <flux:table.column>{{ __('app.item_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.quantity') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column sortable sorted direction="desc">{{ __('app.date') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($requests as $request)
                <flux:table.row :key="$request->id">
                    <flux:table.cell>{{ $request->id }}</flux:table.cell>
                    <flux:table.cell>{{ $request->user?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $request->name }}</flux:table.cell>
                    <flux:table.cell>{{ $request->quantity }}</flux:table.cell>
                    <flux:table.cell>
                        @php
                            $status = $request->approvalStatus?->status;
                        @endphp
                        <flux:badge color="{{ match($status) {
                            'Approved' => 'green',
                            'Rejected' => 'red',
                            'Pending', 'Submitted' => 'yellow',
                            default => 'zinc'
                        } }}">{{ __('app.status_' . strtolower($status)) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <flux:button size="xs" variant="primary" wire:click="$dispatch('panels.workspace.purchase-request.view.show', { id: '{{ $request->id }}' })">{{ __('app.view') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
