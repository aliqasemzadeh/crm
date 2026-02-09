<?php

namespace App\Livewire\Panels\Workspace\Review\User;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.review.user.index');
    }
}
