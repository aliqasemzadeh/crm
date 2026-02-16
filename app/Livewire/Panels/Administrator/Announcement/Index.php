<?php

namespace App\Livewire\Panels\Administrator\Announcement;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Announcement;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function announcements()
    {
        return Announcement::query()
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', $search)
                        ->orWhere('title', 'like', $search)
                        ->orWhere('content', 'like', $search);
                });
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(20);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $this->authorize('administrator_announcement_delete');
        Announcement::findOrFail($id)->delete();
        $this->dispatch('panels.administrator.announcement.index.render');
    }

    #[Layout('layouts.panels.administrator')]
    #[On('panels.administrator.announcement.index.render')]
    public function render()
    {
        $this->authorize('administrator_announcement_index');

        return view('livewire.panels.administrator.announcement.index');
    }
}
