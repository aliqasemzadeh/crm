<?php

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
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();

            // Admission User Detail
            $table->bigInteger('admission_user_id');
            $table->longText('admission_description')->nullable();

            // Repair Status Detail
            $table->string('status')->default('pending'); //pending, rejected, repair, check,warranty, done, delivered , invoice,confirmation
            $table->string('status_description')->nullable();
            $table->dateTime('status_date')->nullable();
            $table->dateTime('estimate_date')->nullable();

            // Repair Owner Detail
            $table->string('owner_name');
            $table->string('owner_mobile');
            $table->string('owner_email')->nullable();
            $table->string('owner_national_code')->nullable();
            $table->string('owner_address')->nullable();

            // Repair Device Detail
            $table->string('warranty_type')->default('none');
            $table->dateTime('warranty_date')->nullable();
            $table->string('device_serial_number');
            $table->string('device_brand');
            $table->string('device_type');
            $table->string('device_model');
            $table->string('device_color')->nullable();
            $table->string('device_image')->nullable();
            $table->longText('device_problem');
            $table->longText('device_accessories')->nullable();
            $table->longText('device_description')->nullable();
            $table->longText('device_problem_file')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
