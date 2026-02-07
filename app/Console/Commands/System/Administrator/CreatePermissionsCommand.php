<?php

namespace App\Console\Commands\System\Administrator;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreatePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:administrator:create-permissions-command';

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
        foreach (__('roles') as $role => $roleTitle) {
            $user = Role::findByName($role);
            $permissions = __('permissions.'.$role);

            foreach ($permissions as $permission => $translate) {
                Permission::firstOrCreate(
                    ['name' => $permission]
                );
            }

            foreach ($permissions as $permission => $translate) {
                $user->givePermissionTo($permission);
            }
        }
    }
}
