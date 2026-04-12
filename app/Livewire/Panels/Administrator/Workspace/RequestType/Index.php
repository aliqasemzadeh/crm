<?php

namespace App\Livewire\Panels\Administrator\Workspace\RequestType;

use Livewire\Component;

use App\Models\Workspace\RequestType;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    protected $listeners = [
        'refresh-request-types' => '$refresh',
    ];

    public function delete($id)
    {
        $requestType = RequestType::findOrFail($id);
        $requestType->delete();

        $this->dispatch('toast', heading: __('app.delete'), text: __('app.deleted_successfully'), variant: 'success');
    }

    public function render()
    {
        $requestTypes = RequestType::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('title', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.panels.administrator.workspace.request-type.index', [
            'requestTypes' => $requestTypes,
        ]);
    }
}
