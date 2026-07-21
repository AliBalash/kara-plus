<?php

use App\Models\Car;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('car_status_periods')) {
            Schema::create('car_status_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
                $table->string('status', 50);
                $table->boolean('availability')->default(false);
                $table->string('reason', 50)->nullable();
                $table->string('manual_status', 50)->nullable();
                $table->string('manual_reason', 50)->nullable();
                $table->string('source', 30)->default('manual');
                $table->text('note')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('trigger_type', 80)->nullable();
                $table->unsignedBigInteger('trigger_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['car_id', 'ended_at'], 'car_status_periods_car_active_idx');
                $table->index(['car_id', 'started_at', 'ended_at'], 'car_status_periods_car_window_idx');
                $table->index(['status', 'reason'], 'car_status_periods_status_reason_idx');
                $table->index(['source', 'started_at'], 'car_status_periods_source_started_idx');
                $table->index(['trigger_type', 'trigger_id'], 'car_status_periods_trigger_idx');
            });
        }

        if (! Schema::hasTable('car_status_periods')) {
            return;
        }

        Car::query()
            ->whereDoesntHave('statusPeriods')
            ->orderBy('id')
            ->chunkById(200, function ($cars) {
                $now = now();

                foreach ($cars as $car) {
                    $operationalStatus = $car->operationalStatus();

                    DB::table('car_status_periods')->insert([
                        'car_id' => $car->id,
                        'status' => $operationalStatus,
                        'availability' => (bool) $car->availability,
                        'reason' => $operationalStatus === Car::STATUS_UNAVAILABLE ? $car->unavailability_reason : null,
                        'manual_status' => $car->resolvedManualStatus(),
                        'manual_reason' => $car->resolvedManualUnavailabilityReason(),
                        'source' => 'migration',
                        'note' => 'Initial status period created from current car state.',
                        'started_at' => $car->updated_at ?? $car->created_at ?? $now,
                        'ended_at' => null,
                        'started_by' => null,
                        'ended_by' => null,
                        'trigger_type' => 'migration',
                        'trigger_id' => null,
                        'metadata' => json_encode([
                            'legacy_status' => $car->status,
                            'legacy_availability' => (bool) $car->availability,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_status_periods');
    }
};
