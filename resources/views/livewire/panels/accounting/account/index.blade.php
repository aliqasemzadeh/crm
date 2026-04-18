<div class="space-y-6">
    <flux:header>
        <flux:heading size="xl">{{ __('app.accounts') }}</flux:heading>
    </flux:header>

    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <flux:accordion transition>
            @foreach($this->accounts as $account)
                <livewire:panels.accounting.account.item :account="$account" :key="$account->AccountId" />
            @endforeach
        </flux:accordion>
    </div>
</div>
