<?php

namespace App\Livewire\Panels\Administrator\Announcement;

use App\Models\Announcement;
use App\Jobs\Notification\BaleSendMessageJob;
use Flux\Flux;
use Livewire\Component;

class Create extends Component
{
    public string $title = '';
    public string $content = '';
    public string $icon = '';
    public string $color = 'zinc';
    public string $link = '';
    public $starts_at;
    public $ends_at;
    public bool $is_active = true;

    public function create()
    {
        $this->authorize('administrator_announcement_create');

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

        Announcement::create($validated);

        BaleSendMessageJob::dispatch(__('app.announcement_bale_message') . PHP_EOL . $this->title);

        $this->reset(['title', 'content', 'icon', 'color', 'link', 'starts_at', 'ends_at', 'is_active']);
        $this->color = 'zinc';
        $this->is_active = true;

        $this->dispatch('panels.administrator.announcement.index.render');
        Flux::modal('panels.administrator.announcement.create.modal')->close();
    }

    public function render()
    {
        return view('livewire.panels.administrator.announcement.create');
    }
}
