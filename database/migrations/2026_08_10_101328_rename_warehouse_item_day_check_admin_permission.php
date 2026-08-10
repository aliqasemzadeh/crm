<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'warehouse_item_day_check_admin')
            ->update(['name' => 'administrator_item_day_check']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'administrator_item_day_check')
            ->update(['name' => 'warehouse_item_day_check_admin']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
