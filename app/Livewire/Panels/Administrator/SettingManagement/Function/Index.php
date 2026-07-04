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

    public function runDayCheck()
    {
        Artisan::call('app:day-check-creation-command');
        Flux::toast(__('app.day_check.command_executed'));
    }

    public function runPriceNotification()
    {
        Artisan::call('app:price-text-message-notification-command');
        Flux::toast(__('app.price_notification_executed'));
    }

    public function runRecurringTasks()
    {
        Artisan::call('app:workspace:generate-recurring-tasks');
        Flux::toast(__('app.recurring_tasks_executed'));
    }

    public function runFollowUp()
    {
        Artisan::call('app:follow-up-command');
        Flux::toast(__('app.follow_up_executed'));
    }

    public function runInvoiceAlert()
    {
        Artisan::call('app:sepidar:alert-on-new-invoice-command');
        Flux::toast(__('app.invoice_alert_executed'));
    }

    public function runImportPhones()
    {
        Artisan::call('app:voip:import-phones-from-sepidar');
        Flux::toast(__('app.import_phones_executed'));
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        return view('livewire.panels.administrator.setting-management.function.index');
    }
}
