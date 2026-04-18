<?php

use Livewire\Component;
use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $search = '';

    #[Computed]
    public function results()
    {
        if (strlen($this->search) < 2) {
            return [];
        }

        return Item::query()
            ->with('image')
            ->where(function ($query) {
                $query->where('Code', 'like', '%' . $this->search . '%')
                    ->orWhere('Title', 'like', '%' . $this->search . '%');
            })
            ->limit(10)
            ->get();
    }
};
?>

<div>
    <flux:modal.trigger name="search" shortcut="cmd.k">
        <div class="flex items-center gap-2 px-2 lg:px-6 py-2">
            <flux:button variant="ghost" icon="magnifying-glass" class="lg:hidden" />
            <flux:input as="button" placeholder="{{ __('app.search_placeholder') }}" icon="magnifying-glass" kbd="⌘K" class="hidden lg:flex w-full max-w-md" />
        </div>
    </flux:modal.trigger>

    <flux:modal name="search" variant="bare" class="w-full max-w-[30rem] my-[12vh] max-h-screen overflow-y-hidden" x-on:close="$wire.set('search', '', false)">
        <flux:command class="border-none shadow-lg inline-flex flex-col max-h-[76vh]">
            <flux:command.input wire:model.live.debounce.300ms="search" placeholder="{{ __('app.search_placeholder') }}" closable />

            <flux:command.items>
                @foreach ($this->results as $item)
                    <flux:command.item :key="$item->ItemID" icon="newspaper">
                        <div class="flex items-center gap-3">
                            @if($item->image)
                                <img src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}" class="w-8 h-8 rounded shadow-sm">
                            @else
                                <div class="w-8 h-8 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center">
                                    <flux:icon name="photo" variant="micro" class="text-zinc-400" />
                                </div>
                            @endif
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $item->Title }}</span>
                                <span class="text-xs text-zinc-500">{{ $item->Code }}</span>
                            </div>
                        </div>
                    </flux:command.item>
                @endforeach

            </flux:command.items>
        </flux:command>
    </flux:modal>
</div>
