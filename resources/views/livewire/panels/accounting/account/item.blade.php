<flux:accordion.item :expanded="$isExpanded">
    <flux:accordion.heading wire:click="toggle">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <span class="text-zinc-500 font-mono text-sm">{{ $account->Code }}</span>
                <span class="font-medium">{{ $account->Title }}</span>
                @if($account->Type)
                    <flux:badge size="sm" inset="top bottom">{{ $account->Type }}</flux:badge>
                @endif
            </div>
            <div class="flex gap-2">
                @foreach($account->topics as $topic)
                    <flux:badge size="sm" color="zinc" variant="outline">{{ $topic->Topic }}</flux:badge>
                @endforeach
            </div>
        </div>
    </flux:accordion.heading>

    <flux:accordion.content>
        <div class="pr-6 border-r-2 border-zinc-100 dark:border-zinc-800 space-y-2">
            @if($isExpanded)
                @if($this->children->count() > 0)
                    <flux:accordion transition>
                        @foreach($this->children as $child)
                            <livewire:panels.accounting.account.item :account="$child" :key="$child->AccountId" />
                        @endforeach
                    </flux:accordion>
                @else
                    <div class="py-2 text-zinc-500 text-sm italic">
                        {{ __('app.no_results_found') }}
                    </div>
                @endif
            @endif
        </div>
    </flux:accordion.content>
</flux:accordion.item>
