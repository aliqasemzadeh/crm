<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_devices')->whereNull('is_approved')->update(['is_approved' => false]);
        DB::table('hr_records')->whereNull('is_device_approved')->update(['is_device_approved' => false]);
    }

    public function down(): void
    {
        //
    }
};
