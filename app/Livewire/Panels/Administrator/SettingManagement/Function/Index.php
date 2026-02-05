<?php

namespace App\Livewire\Panel\Administrator\SettingManagement\Function;

use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function updatePermissions()
    {
        Artisan::call('system:administrator:create-permissions-command');
        Flux::toast(__('app.permissions_updated'));
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Flux::toast(__('app.cache_cleared'));
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        return view('livewire.panel.administrator.setting-management.function.index');
    }
}
