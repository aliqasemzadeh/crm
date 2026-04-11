<?php

namespace App\Livewire\Panels\Workspace\Instruction;

use App\Models\Workspace\Instruction;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Create extends Component
{
    public $title;
    public $body;
    public $status = 'draft';

    protected $rules = [
        'title' => 'required|min:3',
        'body' => 'required',
        'status' => 'required|in:draft,finalized',
    ];

    public function save()
    {
        $this->validate();

        $instruction = Instruction::create([
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('panels.workspace.instruction.edit', $instruction);
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.instruction.create');
    }
}
