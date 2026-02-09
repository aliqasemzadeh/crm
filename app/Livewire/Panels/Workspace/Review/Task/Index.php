<?php

namespace App\Livewire\Panels\Workspace\Review\Task;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.review.task.index');
    }
}
