<?php

namespace App\Livewire\Panels\ServiceCenter\Assembly;

use App\Enums\AssemblyLogTypeEnum;
use App\Enums\AssemblyStatusEnum;
use App\Models\ServiceCenter\Assembly;
use App\Models\ServiceCenter\AssemblyLog;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Logs extends Component
{
    public ?Assembly $assembly = null;

    public string $type = 'note';

    public string $status = '';

    public string $description = '';

    #[On('panels.service-center.assembly.logs.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_assembly_logs');

        $this->assembly = Assembly::query()->with('order')->findOrFail($id);
        $this->type = AssemblyLogTypeEnum::Note->value;
        $this->status = $this->assembly->status instanceof AssemblyStatusEnum
            ? $this->assembly->status->value
            : (string) $this->assembly->status;
        $this->description = '';

        Flux::modal('panels.service-center.assembly.logs.modal')->show();
    }

    #[Computed]
    public function logsList()
    {
        if (! $this->assembly) {
            return collect();
        }

        return AssemblyLog::query()
            ->with('user')
            ->where('assembly_id', $this->assembly->id)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function typeOptions(): array
    {
        return AssemblyLogTypeEnum::options();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return AssemblyStatusEnum::options();
    }

    public function addLog(): void
    {
        $this->authorize('service_center_assembly_logs');

        if (! $this->assembly) {
            return;
        }

        $this->validate([
            'type' => ['required', 'string'],
            'status' => ['required', 'string'],
            'description' => ['required', 'string', 'min:3'],
        ]);

        $type = AssemblyLogTypeEnum::tryFrom($this->type) ?? AssemblyLogTypeEnum::Note;
        $status = AssemblyStatusEnum::tryFromSafe($this->status);

        AssemblyLog::create([
            'assembly_order_id' => $this->assembly->assembly_order_id,
            'assembly_id' => $this->assembly->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'status' => $status->value,
            'description' => $this->description,
        ]);

        if (in_array($type, [AssemblyLogTypeEnum::StatusChange, AssemblyLogTypeEnum::WarrantyReturn], true)) {
            $this->assembly->applyStatus($status, $this->description);
        }

        $this->description = '';
        unset($this->logsList);

        Flux::toast(__('app.assembly_saved'));
        $this->dispatch('panels.service-center.assembly.index.render');
    }

    public function deleteLog(int $id): void
    {
        $this->authorize('service_center_assembly_logs');

        AssemblyLog::query()
            ->where('assembly_id', $this->assembly?->id)
            ->whereKey($id)
            ->delete();

        unset($this->logsList);
        Flux::toast(__('app.assembly_saved'));
    }

    public function formatDate($date): string
    {
        return Jalalian::fromCarbon($date)->format('Y/m/d H:i');
    }

    public function render()
    {
        return view('livewire.panels.service-center.assembly.logs');
    }
}
