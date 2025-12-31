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
        Schema::create('cars', function (Blueprint $table) {
            $table->id(); // شناسه خودرو
            $table->unsignedBigInteger('car_model_id'); // ارجاع به مدل خودرو
            $table->string('plate_number')->unique(); // شماره پلاک خودرو
            $table->enum('status', ['available', 'pre_reserved', 'reserved', 'under_maintenance'])->default('available'); // وضعیت خودرو
            $table->boolean('availability')->default(true); // در دسترس بودن خودرو
            $table->integer('mileage')->default(0); // میزان پیمایش خودرو
            $table->decimal('price_per_day_short', 10, 2); // 1 تا 6 روز
            $table->decimal('price_per_day_mid', 10, 2)->nullable(); // 7 تا 28 روز
            $table->decimal('price_per_day_long', 10, 2)->nullable(); // 28 روز به بالا            
            $table->decimal('ldw_price_short', 10, 2)->default(0);
            $table->decimal('ldw_price_mid', 10, 2)->default(0);
            $table->decimal('ldw_price_long', 10, 2)->default(0);
            $table->decimal('scdw_price_short', 10, 2)->default(0);
            $table->decimal('scdw_price_mid', 10, 2)->default(0);
            $table->decimal('scdw_price_long', 10, 2)->default(0);
            $table->date('service_due_date')->nullable(); // تاریخ موعد سرویس
            $table->text('damage_report')->nullable(); // گزارش خسارت
            $table->unsignedBigInteger('manufacturing_year'); // سال ساخت خودرو
            $table->string('color')->nullable(); // رنگ خودرو
            $table->string('chassis_number')->unique(); // شماره شاسی
            $table->boolean('gps')->default(false); // وضعیت GPS
            $table->boolean('is_company_car')->default(true)->after('gps');
            $table->string('ownership_type')->default('company')->after('is_company_car');

            $table->date('issue_date')->nullable(); // تاریخ شروع ثبت‌نام
            $table->date('expiry_date')->nullable(); // تاریخ انقضای ثبت‌نام
            $table->date('passing_date')->nullable(); //  پاسینگ = معاینه فنی   تاریخ عبور
            $table->integer('passing_valid_for_days')->nullable(); // تعداد روزهای اعتبار عبور
            $table->enum('passing_status', ['done', 'pending', 'failed'])->default('done'); // وضعیت ثبت‌نام
            $table->integer('registration_valid_for_days')->nullable(); // رجیستری خودرو = هر سال کارت ماشین تمدید میشه تعداد روزهای اعتبار ثبت‌نام
            $table->enum('registration_status', ['done', 'pending', 'failed'])->default('done'); // وضعیت ثبت‌نام

            $table->text('notes')->nullable(); // یادداشت‌ها
            $table->timestamps(); // زمان‌های ایجاد و ویرایش

            // Foreign key relationship with car_models
            $table->foreign('car_model_id')->references('id')->on('car_models')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
