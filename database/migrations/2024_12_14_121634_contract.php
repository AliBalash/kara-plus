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
            
        Schema::create('contracts', function (Blueprint $table) {
            $table->id(); // شناسه قرارداد
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // ارجاع به جدول کاربران (کارشناس)
            $table->unsignedBigInteger('customer_id'); // ارجاع به جدول مشتریان
            $table->unsignedBigInteger('car_id'); // ارجاع به جدول خودروها
            $table->date('start_date'); // تاریخ شروع اجاره
            $table->date('end_date')->nullable(); // تاریخ پایان اجاره
            $table->decimal('total_price', 10, 2); // مبلغ کل اجاره
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active'); // وضعیت قرارداد
            $table->text('notes')->nullable(); // یادداشت‌ها
            $table->timestamps();
        
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};