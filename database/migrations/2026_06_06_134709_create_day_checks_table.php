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
        Schema::create('day_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('check'); // check, send, reject, approve
            $table->json('items'); // Array of Item IDs
            $table->json('item_stocks'); // Array of actual stock values (hidden from user)
            $table->json('item_checks')->nullable(); // Array of values entered by user
            $table->text('user_comment')->nullable();
            $table->text('admin_comment')->nullable();
            $table->timestamp('check_at')->nullable();
            $table->timestamp('approve_at')->nullable();
            $table->timestamp('reject_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_checks');
    }
};
