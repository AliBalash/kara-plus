<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_price_windows', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->foreign('operation_id')->references('id')->on('fleet_price_operations')->restrictOnDelete();
            $table->string('name', 120);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['active', 'starts_on', 'ends_on']);
        });
        Schema::create('fleet_price_window_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_price_window_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('tier', 8);
            $table->string('method', 24);
            $table->decimal('value', 10, 2);
            $table->unique(['fleet_price_window_id', 'car_id', 'tier'], 'fleet_window_car_tier_unique');
            $table->index(['car_id', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_price_window_rates');
        Schema::dropIfExists('fleet_price_windows');
    }
};
