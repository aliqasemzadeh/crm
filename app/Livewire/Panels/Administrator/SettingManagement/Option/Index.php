<?php

namespace App\Livewire\Panels\Administrator\SettingManagement\Option;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        return view('livewire.panels.administrator.setting-management.option.index');
    }
}
