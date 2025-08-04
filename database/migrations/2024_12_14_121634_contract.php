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
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // ارجاع به جدول کاربران (کارشناس)
            $table->unsignedBigInteger('customer_id'); // ارجاع به جدول مشتریان
            $table->unsignedBigInteger('car_id'); // ارجاع به جدول خودروها
            $table->string('agent_sale')->nullable(); // نام نماینده‌ی فروش
            $table->dateTime('pickup_date'); // تاریخ و ساعت تحویل
            $table->dateTime('return_date'); // تاریخ و ساعت بازگشت
            $table->string('pickup_location'); // مکان تحویل خودرو
            $table->string('return_location'); // مکان بازگشت خودرو
            $table->decimal('total_price', 10, 2); // مبلغ کل اجاره
            $table->enum('current_status', [
                'pending',
                'assigned',
                'under_review',
                'reserved',
                'delivery',
                'agreement_inspection',
                'awaiting_return',
                'returned',
                'complete',
                'cancelled',
                'rejected'
            ])->default('pending'); // وضعیت قرارداد
            $table->text('notes')->nullable(); // یادداشت‌ها
            $table->json('meta')->nullable();

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
