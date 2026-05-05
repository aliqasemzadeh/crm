<?php

namespace App\Livewire\Panels\Workspace\Review\User\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use Illuminate\Support\Str;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Create extends Component
{
    public User $user;
    public string $title = '';
    public string $description = '';
    public string $status = 'planning';
    public string $priority = 'medium';
    public $due_at;
    public string $repeat_type = 'none';
    public ?string $repeat_weekday = null;
    public ?int $repeat_monthday = null;
    public bool $is_locked = false;
    public array $checklistItems = [];

    #[On('panels.workspace.review.user.task.create.assign-data')]
    public function assignData($status)
    {
        $this->reset(['title', 'description', 'due_at']);
        $this->status = $status;
        $this->priority = 'medium';
        Flux::modal('review-user-task-create-modal')->show();
    }

    protected function rules()
    {
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,doing,done',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
            'repeat_type' => 'required|in:none,daily,weekly,monthly',
            'repeat_weekday' => 'nullable|required_if:repeat_type,weekly|integer|between:0,6',
            'repeat_monthday' => 'nullable|required_if:repeat_type,monthly|integer|between:1,31',
            'is_locked' => 'boolean',
        ];
    }

    public function addChecklistItem(): void
    {
        $this->checklistItems[] = ['id' => (string) Str::uuid(), 'title' => ''];
    }

    public function removeChecklistItem(string $itemId): void
    {
        $this->checklistItems = array_values(array_filter(
            $this->checklistItems,
            fn ($item) => $item['id'] !== $itemId
        ));
    }

    public function sortChecklist($itemId, $position): void
    {
        $items = collect($this->checklistItems);
        $currentIndex = $items->search(fn ($item) => $item['id'] === $itemId);
        if ($currentIndex === false) {
            return;
        }

        $item = $items->pull($currentIndex);
        $items->splice((int) $position, 0, [$item]);
        $this->checklistItems = $items->values()->all();
    }

    public function create()
    {
        $validated = $this->validate();
        $validated['created_by'] = auth()->id();
        $validated['source'] = 'manager';
        $validated['locked_by'] = $validated['is_locked'] ? auth()->id() : null;
        $validated['locked_at'] = $validated['is_locked'] ? now() : null;

        $task = Task::create($validated);
        $task->users()->syncWithoutDetaching([
            $this->user->id => ['role' => 'assignee', 'assigned_at' => now(), 'assigned_by' => auth()->id()]
        ]);

        foreach (collect($this->checklistItems)->values() as $index => $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $task->checklists()->create([
                'title' => $title,
                'order' => $index + 1,
            ]);
        }

        try {
            \App\Jobs\Notification\SendSmsMessageJob::dispatch($this->user->mobile, "فعالیت:" . $task->title . " در میزکار شما قرارگرفت.");
        } catch (\Exception $e) {
            // Log error or ignore
        }

        Flux::toast(__('app.task.notifications.created'));
        $this->dispatch('panels.workspace.review.user.board.render');
        Flux::modal('review-user-task-create-modal')->close();
    }

    public function render()
    {
        return view('livewire.panels.workspace.review.user.task.create');
    }
}
