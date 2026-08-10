<?php

namespace App\Livewire\Panels\Administrator\SettingManagement\Function;

use App\Jobs\UpdateProjectJob;
use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public string $artisanCommand = '';

    public string $commandOutput = '';

    /**
     * Dangerous artisan commands that must never run from the UI.
     *
     * @var list<string>
     */
    protected array $dangerousCommands = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
        'db:seed',
        'tinker',
        'env',
        'key:generate',
        'serve',
        'sail',
        'pail',
        'queue:work',
        'queue:listen',
        'queue:flush',
        'horizon',
        'horizon:terminate',
        'schedule:work',
        'schedule:interrupt',
        'down',
        'vendor:publish',
    ];

    public function updatePermissions(): void
    {
        $this->runFixedCommand(
            ['system:administrator:create-roles-command', 'system:administrator:create-permissions-command'],
            __('app.permissions_updated')
        );
    }

    public function clearCache(): void
    {
        $this->runFixedCommand(['cache:clear'], __('app.cache_cleared'));
    }

    public function updateProject(): void
    {
        UpdateProjectJob::dispatch();
        $this->commandOutput = __('app.project_updated');
        Flux::toast(__('app.project_updated'));
    }

    public function runDayCheck(): void
    {
        $this->runFixedCommand(['app:day-check-creation-command'], __('app.day_check.command_executed'));
    }

    public function runPriceNotification(): void
    {
        $this->runFixedCommand(['app:price-text-message-notification-command'], __('app.price_notification_executed'));
    }

    public function runRecurringTasks(): void
    {
        $this->runFixedCommand(['app:workspace:generate-recurring-tasks'], __('app.recurring_tasks_executed'));
    }

    public function runFollowUp(): void
    {
        $this->runFixedCommand(['app:follow-up-command'], __('app.follow_up_executed'));
    }

    public function runInvoiceAlert(): void
    {
        $this->runFixedCommand(['app:sepidar:alert-on-new-invoice-command'], __('app.invoice_alert_executed'));
    }

    public function runImportPhones(): void
    {
        $this->runFixedCommand(['app:voip:import-phones-from-sepidar'], __('app.import_phones_executed'));
    }

    public function runArtisanCommand(): void
    {
        $raw = trim($this->artisanCommand);

        if ($raw === '') {
            Flux::toast(__('app.artisan_command_required'));

            return;
        }

        $raw = preg_replace('/^(php\s+)?artisan\s+/i', '', $raw) ?? $raw;
        $raw = trim($raw);

        if ($raw === '') {
            Flux::toast(__('app.artisan_command_required'));

            return;
        }

        if (preg_match('/[;&|`$<>\n\r]/', $raw)) {
            $this->commandOutput = __('app.artisan_command_blocked');
            Flux::toast(__('app.artisan_command_blocked'));

            return;
        }

        $tokens = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            Flux::toast(__('app.artisan_command_required'));

            return;
        }

        $commandName = strtolower($tokens[0]);

        if ($this->isDangerousCommand($commandName)) {
            $this->commandOutput = __('app.artisan_command_blocked');
            Flux::toast(__('app.artisan_command_blocked'));

            return;
        }

        try {
            $result = Process::path(base_path())
                ->timeout(300)
                ->run(array_merge([PHP_BINARY, base_path('artisan')], $tokens));

            $output = trim($result->output()."\n".$result->errorOutput());
            $this->commandOutput = $output !== '' ? $output : __('app.artisan_no_output');

            if ($result->successful()) {
                Flux::toast(__('app.artisan_command_executed'));
            } else {
                Flux::toast(__('app.artisan_command_failed'));
            }
        } catch (Throwable $e) {
            $this->commandOutput = $e->getMessage();
            Flux::toast(__('app.artisan_command_failed'));
        }
    }

    /**
     * @param  list<string>  $commands
     */
    protected function runFixedCommand(array $commands, string $successMessage): void
    {
        $outputs = [];

        try {
            foreach ($commands as $command) {
                Artisan::call($command);
                $output = trim(Artisan::output());
                $outputs[] = trim($command."\n".($output !== '' ? $output : __('app.artisan_no_output')));
            }

            $this->commandOutput = implode("\n\n", $outputs);
            Flux::toast($successMessage);
        } catch (Throwable $e) {
            $this->commandOutput = $e->getMessage();
            Flux::toast(__('app.artisan_command_failed'));
        }
    }

    protected function isDangerousCommand(string $commandName): bool
    {
        foreach ($this->dangerousCommands as $dangerous) {
            if ($commandName === $dangerous || str_starts_with($commandName, $dangerous.':')) {
                return true;
            }
        }

        return false;
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        return view('livewire.panels.administrator.setting-management.function.index');
    }
}
