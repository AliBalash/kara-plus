<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->index(['current_status', 'return_date'], 'contracts_status_return_ai_idx');
            $table->index('pickup_date', 'contracts_pickup_ai_idx');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['approval_status', 'payment_date'], 'payments_approval_date_ai_idx');
        });
        Schema::table('insurances', function (Blueprint $table): void {
            $table->index(['car_id', 'id'], 'insurances_car_latest_ai_idx');
            $table->index('expiry_date', 'insurances_expiry_ai_idx');
        });
        Schema::table('cars', function (Blueprint $table): void {
            $table->index('service_due_date', 'cars_service_due_ai_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cars', fn (Blueprint $table) => $table->dropIndex('cars_service_due_ai_idx'));
        Schema::table('insurances', function (Blueprint $table): void {
            $table->dropIndex('insurances_expiry_ai_idx');
            $table->dropIndex('insurances_car_latest_ai_idx');
        });
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex('payments_approval_date_ai_idx'));
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropIndex('contracts_pickup_ai_idx');
            $table->dropIndex('contracts_status_return_ai_idx');
        });
    }
};
