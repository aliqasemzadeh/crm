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
        if (! config('main.active_day_check.crm')) {
            $this->info('CRM day check is disabled. Skipping command execution.');

            return;
        }

        $crmUsers = config('main.crm_users', []);

        if (empty($crmUsers)) {
            $this->info('No CRM users found in config/main.php');
            return;
        }

        $totalNeeded = array_sum($crmUsers);

        // Get customer IDs that had a follow-up in the last 30 months
        $excludedCustomerIds = \App\Models\Crm\FollowUp::where('created_at', '>=', now()->subMonths(30))
            ->pluck('customer_id')
            ->unique()
            ->toArray();

        $threeMonthsAgo = now()->subMonths(3);
        $totalToFetch = $totalNeeded * 2;
        $halfNeeded = (int) ceil($totalToFetch / 2);

        // Get customers registered in the last 3 months
        $newCustomers = \App\Models\SetareganCo\User::whereNotIn('Id', $excludedCustomerIds)
            ->where('RegisterDate', '>=', $threeMonthsAgo)
            ->inRandomOrder()
            ->limit($halfNeeded)
            ->pluck('Id')
            ->toArray();

        // Get older customers
        $oldCustomers = \App\Models\SetareganCo\User::whereNotIn('Id', $excludedCustomerIds)
            ->where(function($query) use ($threeMonthsAgo) {
                $query->where('RegisterDate', '<', $threeMonthsAgo)
                      ->orWhereNull('RegisterDate');
            })
            ->inRandomOrder()
            ->limit($halfNeeded)
            ->pluck('Id')
            ->toArray();

        $customers = array_merge($newCustomers, $oldCustomers);
        shuffle($customers);

        if (empty($customers)) {
            $this->error('No eligible customers found in SetareganCo database.');
            return;
        }

        $customerIndex = 0;
        foreach ($crmUsers as $agentId => $count) {
            $this->info("Creating {$count} follow-ups for agent ID: {$agentId}");

            for ($i = 0; $i < $count; $i++) {
                if (!isset($customers[$customerIndex])) {
                    $this->warn("Ran out of unique customers for agent ID: {$agentId}");
                    break;
                }

                \App\Models\Crm\FollowUp::create([
                    'agent_id' => $agentId,
                    'customer_id' => $customers[$customerIndex],
                    'status' => \App\Enums\FollowUpStatusEnum::PENDING,
                    'due_date' => now(),
                ]);

                $customerIndex++;
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
