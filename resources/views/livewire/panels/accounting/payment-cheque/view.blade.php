<flux:modal name="panels.accounting.payment-cheque.view.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.payment_cheque_details') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.payment_cheque_details_description') }}</flux:text>
        </div>

        @if(isset($cheque))
            <div class="grid grid-cols-1 gap-4">
                <flux:card>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.number') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->Number }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.sayad_code') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->SayadCode ?? '-' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.customer') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->dl->Title ?? $cheque->DlRef }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.amount') }}:</flux:text>
                            <flux:text class="font-bold">{{ number_format($cheque->Amount) }} {{ __('app.rial') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.date') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}</flux:text>
                        </div>
                        <flux:separator />
                        <div class="pt-2">
                            <flux:text>{{ __('app.description') }}:</flux:text>
                            <flux:text class="block mt-1">{{ $cheque->Description ?? '-' }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</flux:modal>
