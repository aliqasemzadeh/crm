<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_salesperson')->default(false)->after('requires_local_network');
            $table->boolean('is_accountant')->default(false)->after('is_salesperson');
            $table->boolean('is_warehouse_staff')->default(false)->after('is_accountant');
            $table->boolean('is_services_staff')->default(false)->after('is_warehouse_staff');
        });

        $this->seedFromConfig();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_salesperson',
                'is_accountant',
                'is_warehouse_staff',
                'is_services_staff',
            ]);
        });
    }

    private function seedFromConfig(): void
    {
        // Legacy seed: older installs used main.crm_users / main.warehouse_users maps.
        $crmUsers = config('main.crm_users', []);
        $warehouseUsers = config('main.warehouse_users', []);
        $staffFlags = config('main.staff_flags', []);

        $map = [
            'is_salesperson' => array_merge(
                is_array($crmUsers) ? array_keys($crmUsers) : [],
                is_array($staffFlags['salesperson'] ?? null) ? $staffFlags['salesperson'] : [],
            ),
            'is_accountant' => is_array($staffFlags['accountant'] ?? null) ? $staffFlags['accountant'] : [],
            'is_warehouse_staff' => array_merge(
                is_array($warehouseUsers) ? array_keys($warehouseUsers) : [],
                is_array($staffFlags['warehouse'] ?? null) ? $staffFlags['warehouse'] : [],
            ),
            'is_services_staff' => is_array($staffFlags['services'] ?? null) ? $staffFlags['services'] : [],
        ];

        foreach ($map as $column => $ids) {
            $ids = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => (int) $id, is_array($ids) ? $ids : []),
                static fn (int $id): bool => $id > 0,
            )));

            if ($ids === []) {
                continue;
            }

            DB::table('users')->whereIn('id', $ids)->update([$column => true]);
        }
    }
};
