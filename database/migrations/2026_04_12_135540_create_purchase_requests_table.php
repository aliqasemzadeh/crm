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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('کاربر ثبت کننده');
            $table->string('name')->comment('نام کالا');
            $table->integer('quantity')->comment('تعداد');
            $table->text('description')->comment('دلیل خرید یا توضیحات');
            $table->decimal('price', 15, 2)->comment('قیمت تقریبی');
            $table->text('supplier')->nullable()->comment('تامین کننده (اختیاری)');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
