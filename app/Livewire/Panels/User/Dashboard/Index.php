<?php

namespace App\Livewire\Panels\User\Dashboard;

use App\Models\Announcement;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class Index extends Component
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

    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.dashboard.index');
    }
}
