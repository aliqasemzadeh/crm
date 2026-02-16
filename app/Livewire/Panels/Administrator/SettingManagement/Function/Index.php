<?php

namespace App\Livewire\Panels\Administrator\SettingManagement\Function;

use App\Jobs\UpdateProjectJob;
use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function updatePermissions()
    {
        Artisan::call('system:administrator:create-roles-command');
        Artisan::call('system:administrator:create-permissions-command');
        Flux::toast(__('app.permissions_updated'));
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Flux::toast(__('app.cache_cleared'));
    }


    public function updateProject()
    {
        UpdateProjectJob::dispatch();
        Flux::toast(__('app.project_updated'));
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        return view('livewire.panels.administrator.setting-management.function.index');
    }
}
