<?php

namespace App\Livewire\Panels\Workspace\Instruction;

use App\Models\Workspace\Instruction;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $filter_status = '';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filter_status'])) {
            $this->resetPage();
        }
    }

    public function deleteInstruction(Instruction $instruction)
    {
        if ($instruction->user_id !== auth()->id()) {
            return;
        }

        $instruction->delete();
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        $query = Instruction::query();

        if ($this->filter_status) {
            $query->where('status', $this->filter_status);
        }

        $instructions = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.panels.workspace.instruction.index', [
            'instructions' => $instructions
        ]);
    }
}
