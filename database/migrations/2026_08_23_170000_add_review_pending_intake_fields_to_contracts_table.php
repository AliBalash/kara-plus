<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONTRACT_STATUSES = [
        'review_pending',
        'pending',
        'assigned',
        'under_review',
        'reserved',
        'delivery',
        'agreement_inspection',
        'awaiting_return',
        'payment',
        'returned',
        'complete',
        'cancelled',
        'rejected',
    ];

    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreignId('requested_car_id')
                ->nullable()
                ->constrained('cars')
                ->nullOnDelete();
            $table->string('intake_source', 32)->default('panel')->index();
            $table->uuid('public_request_uuid')->nullable()->unique();
        });

        $this->changeStatusEnums(self::CONTRACT_STATUSES);
    }

    public function down(): void
    {
        DB::table('contracts')
            ->where('current_status', 'review_pending')
            ->update(['current_status' => 'pending']);
        DB::table('contract_statuses')
            ->where('status', 'review_pending')
            ->update(['status' => 'pending']);

        $this->changeStatusEnums(array_values(array_diff(self::CONTRACT_STATUSES, ['review_pending'])));

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropForeign(['requested_car_id']);
            $table->dropColumn(['requested_car_id', 'intake_source', 'public_request_uuid']);
        });
    }

    /**
     * SQLite stores Laravel enums as text, so it already accepts the new status.
     */
    private function changeStatusEnums(array $statuses): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('contracts', function (Blueprint $table) use ($statuses): void {
                $table->enum('current_status', $statuses)->default('pending')->change();
            });
            Schema::table('contract_statuses', function (Blueprint $table) use ($statuses): void {
                $table->enum('status', $statuses)->change();
            });

            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $quotedStatuses = implode(', ', array_map(
            static fn (string $status): string => "'{$status}'",
            $statuses
        ));

        DB::statement(
            "ALTER TABLE `contracts` MODIFY COLUMN `current_status` ENUM({$quotedStatuses}) NOT NULL DEFAULT 'pending'"
        );
        DB::statement(
            "ALTER TABLE `contract_statuses` MODIFY COLUMN `status` ENUM({$quotedStatuses}) NOT NULL"
        );
    }
};
