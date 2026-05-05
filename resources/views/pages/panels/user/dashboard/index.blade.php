<?php

use App\Models\Announcement;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Collection;

new #[Layout('layouts.panels.user')] class extends Component
{
    public Collection $announcements;

    public function mount()
    {
        $this->loadAnnouncements();
    }

    public function loadAnnouncements()
    {
        $this->announcements = collect();
        if (auth()->user()?->can('user_announcement_display')) {
            $this->announcements = Announcement::active()
                ->with(['users' => function ($query) {
                    $query->where('user_id', auth()->id());
                }])
                ->latest()
                ->get();
        }
    }

    public function markAsRead($announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);
        $announcement->users()->syncWithoutDetaching([
            auth()->id() => ['viewed_at' => now()]
        ]);

        $this->loadAnnouncements();

        \Flux\Flux::toast(__('app.announcement_marked_as_read'));
    }
};

?>
    <div class="max-w-4xl mx-auto p-6 space-y-6">
        @foreach($announcements as $announcement)
            @php
                $isRead = $announcement->users->where('id', auth()->id())->first()?->pivot?->viewed_at;
            @endphp
            <flux:callout :icon="$announcement->icon ?: 'megaphone'"
                          :color="$announcement->color ?: 'zinc'"
                          @class(['mb-4', 'opacity-50 grayscale-[0.5]' => $isRead])>
                <flux:callout.heading>{{ $announcement->title }}</flux:callout.heading>
                <flux:callout.text>{!! $announcement->content !!}</flux:callout.text>

                <x-slot name="actions">
                    @if($announcement->link)
                        <flux:button :href="$announcement->link" variant="ghost">
                            {{ __('app.read_more') }}
                        </flux:button>
                    @endif

                    @if(!$isRead)
                        <flux:button wire:click="markAsRead({{ $announcement->id }})" variant="ghost">
                            {{ __('app.mark_as_read') }}
                        </flux:button>
                    @endif
                </x-slot>
            </flux:callout>
        @endforeach
    </div>
