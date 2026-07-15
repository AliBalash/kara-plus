<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('car_unavailability_periods')) {
            return;
        }

        Schema::create('car_unavailability_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->string('reason', 50);
            $table->text('note')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_note')->nullable();
            $table->timestamps();

            $table->index(['car_id', 'start_date', 'end_date'], 'car_unavailability_periods_car_window_idx');
            $table->index(['start_date', 'end_date'], 'car_unavailability_periods_window_idx');
            $table->index('cancelled_at');
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_unavailability_periods');
    }
};
