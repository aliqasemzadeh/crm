<?php

use App\Models\LastRecordCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_cash_back_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('start_amount');
            $table->unsignedBigInteger('end_amount');
            $table->unsignedBigInteger('cash_back_amount');
            $table->boolean('is_percent')->default(false);
            $table->unsignedInteger('activation_delay_days');
            $table->unsignedInteger('usage_duration_days');
            $table->timestamps();
        });

        LastRecordCheck::query()->firstOrCreate(
            ['model' => 'Models\SetareganCo\Order'],
            ['last_record_id' => 204202]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_cash_back_rules');
    }
};
