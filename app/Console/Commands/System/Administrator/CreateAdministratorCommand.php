<?php

namespace App\Console\Commands\System\Administrator;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdministratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:administrator:create-administrator-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->ask('UserID');
        try {
            $user = User::findOrFail($userId);
            $user->assignRole('user');
            $user->assignRole('administrator');
            $user->assignRole('crm');
            $this->info('User Created Successfully');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

    }
}
