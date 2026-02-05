<?php

namespace App\Livewire\Panel\ServiceCenter\Repair;

use App\Enums\StatusEnum;
use App\Models\ServiceCenter\Repair;
use App\Models\ServiceCenter\RepairLog;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Logs extends Component
{
    public Repair $repair;

    public int $id;

    public string $status = '';
    public string $description = '';

    #[On('panel.service-center.repair.logs.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_logs');

        $this->repair = Repair::findOrFail($id);
        $this->id = $this->repair->id;

        Flux::modal('panel.service-center.repair.logs.modal')->show();
    }

    #[Computed]
    public function logsList()
    {
        if (!isset($this->repair->id)) {
            return collect();
        }

        return RepairLog::where('repair_id', $this->repair->id)
            ->with('technician')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function statusOptions()
    {
        return StatusEnum::options();
    }

    public function addLog(): void
    {
        $this->validate([
            'status' => ['required', 'string'],
            'description' => ['required', 'string', 'min:3'],
        ], [], [
            'status' => __('app.log_status'),
            'description' => __('app.log_description'),
        ]);

        // Validate status is a valid enum value
        $validStatuses = array_column(StatusEnum::options(), 'value');
        if (!in_array($this->status, $validStatuses)) {
            Flux::toast(variant: 'danger', text: __('app.invalid_status'));
            return;
        }

        RepairLog::create([
            'repair_id' => $this->repair->id,
            'technician_user_id' => (string) Auth::id(),
            'description' => $this->description,
            'status' => $this->status,
        ]);

        $this->repair->update([
            'status' => $this->status,
        ]);

        $this->status = '';
        $this->description = '';

        $this->dispatch('$refresh');
        $this->dispatch('panel.service-center.repair.index.render');

        Flux::toast(variant: 'success', text: __('app.log_added'));
    }

    public function deleteLog(int $logId): void
    {
        $log = RepairLog::findOrFail($logId);

        if ($log->repair_id !== $this->repair->id) {
            Flux::toast(variant: 'danger', text: __('app.are_you_sure'));
            return;
        }

        $log->delete();

        $this->dispatch('$refresh');

        Flux::toast(variant: 'success', text: __('app.log_deleted'));
    }

    public function render()
    {
        return view('livewire.panel.service-center.repair.logs');
    }
}
