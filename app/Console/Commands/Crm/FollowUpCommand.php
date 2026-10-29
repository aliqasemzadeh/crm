<?php

namespace App\Console\Commands\Crm;

use App\Enums\FollowUpStatusEnum;
use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Crm\FollowUp;
use App\Models\User;
use Illuminate\Console\Command;

class FollowUpCommand extends Command
{
    protected $signature = 'app:follow-up-command';

    protected $description = 'Create follow-ups for salesperson staff based on their quotas';

    public function handle()
    {
        if (! config('main.active_day_check.crm')) {
            $this->info('CRM day check is disabled. Skipping command execution.');

            return;
        }

        $salespeople = User::query()->withStaffFlag('salesperson')->get();

        if ($salespeople->isEmpty()) {
            $this->info('No salesperson staff found (is_salesperson).');

            return;
        }

        $crmUsers = $salespeople
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->crmFollowUpQuota()])
            ->all();

        $totalNeeded = array_sum($crmUsers);

        $excludedCustomerIds = FollowUp::where('created_at', '>=', now()->subMonths(30))
            ->pluck('customer_id')
            ->unique()
            ->toArray();

        $threeMonthsAgo = now()->subMonths(3);
        $totalToFetch = $totalNeeded * 2;
        $halfNeeded = (int) ceil($totalToFetch / 2);

        $newCustomers = \App\Models\SetareganCo\User::whereNotIn('Id', $excludedCustomerIds)
            ->where('RegisterDate', '>=', $threeMonthsAgo)
            ->inRandomOrder()
            ->limit($halfNeeded)
            ->pluck('Id')
            ->toArray();

        $oldCustomers = \App\Models\SetareganCo\User::whereNotIn('Id', $excludedCustomerIds)
            ->where(function ($query) use ($threeMonthsAgo) {
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
                if (! isset($customers[$customerIndex])) {
                    $this->warn("Ran out of unique customers for agent ID: {$agentId}");
                    break;
                }

                FollowUp::create([
                    'agent_id' => $agentId,
                    'customer_id' => $customers[$customerIndex],
                    'status' => FollowUpStatusEnum::PENDING,
                    'due_date' => now(),
                ]);

                $customerIndex++;
            }

            if ($count >= 10) {
                $user = $salespeople->firstWhere('id', $agentId) ?? User::find($agentId);
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
