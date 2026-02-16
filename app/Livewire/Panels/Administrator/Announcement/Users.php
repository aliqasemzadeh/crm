<?php

namespace App\Livewire\Panels\Administrator\Announcement;

use App\Models\Announcement;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public Announcement $announcement;
    public int $id;

    #[On('panels.administrator.announcement.users.assign-data')]
    public function assignData($id): void
    {
        $this->announcement = Announcement::findOrFail($id);
        $this->id = $this->announcement->id;

        Flux::modal('panels.administrator.announcement.users.modal')->show();
    }

    #[\Livewire\Attributes\Computed]
    public function viewedUsers()
    {
        if (! isset($this->announcement)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return $this->announcement->users()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.panels.administrator.announcement.users');
    }
}
