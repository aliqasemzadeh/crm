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
            $table->unsignedSmallInteger('crm_follow_up_quota')->nullable()->after('is_services_staff');
            $table->unsignedSmallInteger('warehouse_check_quota')->nullable()->after('crm_follow_up_quota');
        });

        // Legacy values formerly in config/main.php crm_users / warehouse_users.
        $crmUsers = config('main.crm_users', [
            2 => 10,
            8 => 10,
        ]);
        if (is_array($crmUsers)) {
            foreach ($crmUsers as $userId => $quota) {
                $userId = (int) $userId;
                if ($userId <= 0) {
                    continue;
                }
                DB::table('users')->where('id', $userId)->update([
                    'is_salesperson' => true,
                    'crm_follow_up_quota' => max(1, (int) $quota),
                ]);
            }
        }

        $warehouseUsers = config('main.warehouse_users', [
            6 => 15,
            11 => 33,
        ]);
        if (is_array($warehouseUsers)) {
            foreach ($warehouseUsers as $userId => $quota) {
                $userId = (int) $userId;
                if ($userId <= 0) {
                    continue;
                }
                DB::table('users')->where('id', $userId)->update([
                    'is_warehouse_staff' => true,
                    'warehouse_check_quota' => max(1, (int) $quota),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['crm_follow_up_quota', 'warehouse_check_quota']);
        });
    }
};
