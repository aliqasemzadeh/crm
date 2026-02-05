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
        $user = Role::findByName('user');
        $permissions_user = __('permissions.user');

        foreach ($permissions_user as $permission => $translate) {
            Permission::firstOrCreate(
                ['name' => $permission]
            );
        }

        foreach ($permissions_user as $permission => $translate) {
            $user->givePermissionTo($permission);
        }

        $administrator = Role::findByname('panel.administrator');
        $permissions_administrator = __('permissions.administrator');

        foreach ($permissions_administrator as $permission => $translate) {
            Permission::firstOrCreate(
                ['name' => $permission]
            );
        }

        foreach ($permissions_administrator as $permission => $translate) {
            $administrator->givePermissionTo($permission);
        }

        $crm = Role::findByName('crm');
        $permissions_crm = __('permissions.crm');

        foreach ($permissions_crm as $permission => $translate) {
            Permission::firstOrCreate(
                ['name' => $permission]
            );
        }

        foreach ($permissions_crm as $permission => $translate) {
            $crm->givePermissionTo($permission);
        }

        $shop = Role::findByName('shop');
        $permissions_shop = __('permissions.shop');

        foreach ($permissions_shop as $permission => $translate) {
            Permission::firstOrCreate(
                ['name' => $permission]
            );
        }

        foreach ($permissions_shop as $permission => $translate) {
            $shop->givePermissionTo($permission);
        }

        $service_center = Role::findByName('service_center');
        $permissions_service_center = __('permissions.service_center');

        foreach ($permissions_service_center as $permission => $translate) {
            Permission::firstOrCreate(
                ['name' => $permission]
            );
        }

        foreach ($permissions_service_center as $permission => $translate) {
            $service_center->givePermissionTo($permission);
        }

        $accounting = Role::findByName('accounting');
        $permissions_accounting = __('permissions.accounting');

        foreach ($permissions_accounting as $permission => $translate) {
            Permission::firstOrCreate(
                ['name' => $permission]
            );
        }

        foreach ($permissions_accounting as $permission => $translate) {
            $accounting->givePermissionTo($permission);
        }

    }
}
