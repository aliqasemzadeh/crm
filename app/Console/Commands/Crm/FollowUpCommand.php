<?php

namespace App\Console\Commands\Crm;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\User;
use Illuminate\Console\Command;

class FollowUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:follow-up-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create follow-ups for CRM users based on configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $crmUsers = config('main.crm_users', []);

        if (empty($crmUsers)) {
            $this->info('No CRM users found in config/main.php');
            return;
        }

        $customers = \App\Models\SetareganCo\User::inRandomOrder()->limit(100)->pluck('Id')->toArray();

        if (empty($customers)) {
            $this->error('No customers found in SetareganCo database.');
            return;
        }

        foreach ($crmUsers as $agentId => $count) {
            $this->info("Creating {$count} follow-ups for agent ID: {$agentId}");

            for ($i = 0; $i < $count; $i++) {
                \App\Models\Crm\FollowUp::create([
                    'agent_id' => $agentId,
                    'customer_id' => $customers[array_rand($customers)],
                    'status' => \App\Enums\FollowUpStatusEnum::PENDING,
                    'due_date' => now(),
                ]);
            }

            if ($count >= 10) {
                $user = User::find($agentId);
                if ($user && $user->mobile) {
                    $message = "در لیست شما {$count} مشتری برای پیگیری ایجاد شده است.";
                    dispatch(new SendSmsMessageJob($user->mobile, $message));
                    $this->info("SMS notification sent to agent ID: {$agentId}");
                }
            }
        }

        $this->info('Follow-ups created successfully.');
    }
}
