<?php

namespace App\Livewire\Panel\ServiceCenter\Dashboard;

use App\Enums\StatusEnum;
use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('service_center_dashboard_index');
    }

    public function sortItem($item, $position)
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::New;

        // Update the repair's status if it changed
        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    public function sortItemDoing($item, $position)
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::InProgress;

        // Update the repair's status if it changed
        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    public function sortItemDone($item, $position)
    {
        $repair = Repair::findOrFail($item);
        $oldStatus = StatusEnum::tryFromSafe($repair->status);
        $targetStatus = StatusEnum::Completed;

        // Update the repair's status if it changed
        if ($oldStatus !== $targetStatus) {
            $repair->status = $targetStatus->value;
            $repair->status_date = now();
            $repair->save();
        }
    }

    #[Computed]
    public function plannedRepairs(): Collection
    {
        return Repair::where('status', StatusEnum::New->value)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function doingRepairs(): Collection
    {
        return Repair::whereIn('status', [
            StatusEnum::InProgress->value,
            StatusEnum::UnderRepair->value,
            StatusEnum::UnderReview->value
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function doneRepairs(): Collection
    {
        return Repair::where('status', StatusEnum::Completed->value)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Layout('layouts.panels.service-center')]
    public function render()
    {
        return view('livewire.panel.service-center.dashboard.index');
    }
}
