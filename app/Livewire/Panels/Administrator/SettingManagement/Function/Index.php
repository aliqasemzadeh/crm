<?php

namespace App\Livewire\Panels\Administrator\SettingManagement\Function;

use App\Jobs\Notification\BaleSendMessageJob;
use App\Jobs\System\UpdateProjectJob;
use App\Models\SystemActionLog;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\Console\Command\Command;
use Throwable;

class Index extends Component
{
    use WithPagination;

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

    public function updateProject(bool $full = true): void
    {
        UpdateProjectJob::dispatch($full);

        $message = $full
            ? __('app.update_project_full_queued')
            : __('app.update_project_quick_queued');

        $this->commandOutput = $message;
        Flux::toast($message);
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

    public function sendCrmBaleTestMessage(): void
    {
        $token = config('bale.crm_bot_token');
        $chatId = config('bale.crm_bot_group_chat_id');

        if (! $token || ! $chatId) {
            $this->commandOutput = __('app.crm_bale_test_missing_config');
            Flux::toast(__('app.crm_bale_test_missing_config'));

            return;
        }

        $message = __('app.crm_bale_test_message', [
            'bot' => (string) config('bale.crm_bot_name', 'SetareganCRMBot'),
            'chat_id' => (string) $chatId,
            'time' => now()->timezone('Asia/Tehran')->format('Y-m-d H:i:s'),
        ]);

        try {
            BaleSendMessageJob::dispatchSync($message, 'crm');

            $this->commandOutput = $message;

            SystemActionLog::create([
                'command' => 'crm:bale-test-message',
                'output' => $message,
                'status' => 'success',
            ]);

            Flux::toast(__('app.crm_bale_test_sent'));
            unset($this->actionLogs);
        } catch (Throwable $e) {
            $this->commandOutput = $e->getMessage();

            SystemActionLog::create([
                'command' => 'crm:bale-test-message',
                'output' => $this->commandOutput,
                'status' => 'failed',
            ]);

            Flux::toast(__('app.crm_bale_test_failed'));
            unset($this->actionLogs);
        }
    }

    public function runPaidOrderBaleNotification(): void
    {
        $this->runFixedCommand(
            ['app:setaregan-co:send-paid-order-bale-notification'],
            __('app.paid_order_bale_notification_executed')
        );
    }

    public function runArtisanCommand(string $command): void
    {
        $command = trim($command);

        if ($command === '') {
            Flux::toast(__('app.artisan_command_required'));

            return;
        }

        if ($this->isDangerousCommand(strtolower($command))) {
            $this->commandOutput = __('app.artisan_command_blocked');
            Flux::toast(__('app.artisan_command_blocked'));

            return;
        }

        try {
            Artisan::call($command);
            $output = trim(Artisan::output());
            $this->commandOutput = $output !== '' ? $output : __('app.artisan_no_output');

            SystemActionLog::create([
                'command' => $command,
                'output' => $this->commandOutput,
                'status' => 'success',
            ]);

            Flux::modal('panels.administrator.setting-management.function.artisan-command')->close();
            Flux::toast(__('app.artisan_command_executed'));
            unset($this->actionLogs);
        } catch (Throwable $e) {
            $this->commandOutput = $e->getMessage();

            SystemActionLog::create([
                'command' => $command,
                'output' => $this->commandOutput,
                'status' => 'failed',
            ]);

            Flux::toast(__('app.artisan_command_failed'));
            unset($this->actionLogs);
        }
    }

    /**
     * @return Collection<int, array{name: string, description: string}>
     */
    #[Computed]
    public function artisanCommands(): Collection
    {
        return collect(Artisan::all())
            ->filter(fn (Command $command, string $name): bool => ! $this->isDangerousCommand(strtolower($name)))
            ->map(fn (Command $command, string $name): array => [
                'name' => $name,
                'description' => (string) $command->getDescription(),
            ])
            ->sortBy('name')
            ->values();
    }

    #[Computed]
    public function actionLogs(): LengthAwarePaginator
    {
        return SystemActionLog::query()
            ->latest()
            ->paginate(10);
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

                SystemActionLog::create([
                    'command' => $command,
                    'output' => $output !== '' ? $output : __('app.artisan_no_output'),
                    'status' => 'success',
                ]);
            }

            $this->commandOutput = implode("\n\n", $outputs);
            Flux::toast($successMessage);
            unset($this->actionLogs);
        } catch (Throwable $e) {
            $this->commandOutput = $e->getMessage();
            Flux::toast(__('app.artisan_command_failed'));
            unset($this->actionLogs);
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
