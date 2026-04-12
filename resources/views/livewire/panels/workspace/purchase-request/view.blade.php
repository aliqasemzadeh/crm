<flux:modal name="panels.workspace.purchase-request.view.modal" class="md:w-[32rem]" flyout position="right">
    @if($request)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.purchase_request_details') }} #{{ $request->id }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.viewing_purchase_request_for', ['name' => $request->name]) }}</flux:text>
        </div>

        <flux:separator />

        <div class="grid grid-cols-2 gap-4">
            <div>
                <flux:label>{{ __('app.item_name') }}</flux:label>
                <flux:text>{{ $request->name }}</flux:text>
            </div>
            <div>
                <flux:label>{{ __('app.quantity') }}</flux:label>
                <flux:text>{{ $request->quantity }}</flux:text>
            </div>
            <div>
                <flux:label>{{ __('app.approximate_price') }}</flux:label>
                <flux:text>{{ number_format($request->price) }}</flux:text>
            </div>
            <div>
                <flux:label>{{ __('app.user') }}</flux:label>
                <flux:text>{{ $request->user?->name }}</flux:text>
            </div>
            <div class="col-span-2">
                <flux:label>{{ __('app.supplier') }}</flux:label>
                <flux:text>{{ $request->supplier ?: __('app.not_specified') }}</flux:text>
            </div>
            <div class="col-span-2">
                <flux:label>{{ __('app.description_reason') }}</flux:label>
                <flux:text>{{ $request->description }}</flux:text>
            </div>
        </div>

        <flux:separator />

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
            <x-ringlesoft-approval-actions :model="$request" />
        </div>
    </div>
    @endif
</flux:modal>
