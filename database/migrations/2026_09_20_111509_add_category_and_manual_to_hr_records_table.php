<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_records', function (Blueprint $table) {
            $table->string('category', 20)->default('work')->after('type');
            $table->boolean('is_manual')->default(false)->after('category');
            $table->unsignedBigInteger('user_device_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hr_records', function (Blueprint $table) {
            $table->dropColumn(['category', 'is_manual']);
            $table->unsignedBigInteger('user_device_id')->nullable(false)->change();
        });
    }
};
