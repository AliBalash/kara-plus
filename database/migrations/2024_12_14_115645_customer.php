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
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // شناسه اصلی
            $table->string('first_name'); // نام
            $table->string('last_name'); // نام خانوادگی
            $table->string('national_code', 10)->unique()->nullable(); // کد ملی 
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // جنسیت
            $table->string('email')->unique(); // ایمیل (منحصر به‌فرد)
            $table->string('phone'); // شماره تماس
            $table->string('messenger_phone'); // شماره تماس
            $table->text('address')->nullable(); // آدرس
            $table->string('passport_number')->unique()->nullable(); // شماره پاسپورت
            $table->date('passport_expiry_date')->nullable(); // تاریخ انقضای پاسپورت
            $table->string('nationality')->nullable(); // ملیت
            $table->date('birth_date')->nullable()->after('nationality');
            $table->string('license_number')->unique()->nullable(); // شماره گواهینامه رانندگی
            $table->enum('status', ['active', 'inactive'])->default('active'); // وضعیت مشتری
            $table->date('registration_date')->nullable(); // تاریخ ثبت مشتری
            $table->timestamps(); // زمان‌های ایجاد و ویرایش
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');

    }
};