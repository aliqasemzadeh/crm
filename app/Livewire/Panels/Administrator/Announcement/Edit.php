<?php

namespace App\Livewire\Panels\Administrator\Announcement;

use App\Models\Announcement;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Edit extends Component
{
    public Announcement $announcement;
    public int $id;

    public string $title = '';
    public string $content = '';
    public string $icon = '';
    public string $color = 'zinc';
    public string $link = '';
    public $starts_at;
    public $ends_at;
    public bool $is_active = true;

    #[On('panels.administrator.announcement.edit.assign-data')]
    public function assignData($id): void
    {
        $this->announcement = Announcement::findOrFail($id);
        $this->id = $this->announcement->id;
        $this->title = $this->announcement->title;
        $this->content = $this->announcement->content;
        $this->icon = (string) $this->announcement->icon;
        $this->color = (string) ($this->announcement->color ?: 'zinc');
        $this->link = (string) $this->announcement->link;
        $this->starts_at = $this->announcement->starts_at?->format('Y-m-d');
        $this->ends_at = $this->announcement->ends_at?->format('Y-m-d');
        $this->is_active = $this->announcement->is_active;

        Flux::modal('panels.administrator.announcement.edit.modal')->show();
    }

    public function edit(): void
    {
        // $this->authorize('administrator_announcement_edit');

        if (! isset($this->announcement)) {
            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $this->announcement->update($validated);

        $this->dispatch('panels.administrator.announcement.index.render');
        Flux::modal('panels.administrator.announcement.edit.modal')->close();
    }

    public function render()
    {
        return view('livewire.panels.administrator.announcement.edit');
    }
}
