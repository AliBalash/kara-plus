<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contract_charges', 'amendment_id')) {
            Schema::table('contract_charges', function (Blueprint $table): void {
                $table->unsignedBigInteger('amendment_id')->nullable()->after('contract_id');
            });
        }

        $columns = [
            'source_type' => fn (Blueprint $table) => $table->string('source_type', 32)->default('original')->after('type'),
            'quantity' => fn (Blueprint $table) => $table->decimal('quantity', 12, 3)->nullable()->after('amount'),
            'unit' => fn (Blueprint $table) => $table->string('unit', 32)->nullable()->after('quantity'),
            'unit_price' => fn (Blueprint $table) => $table->decimal('unit_price', 12, 2)->nullable()->after('unit'),
            'tax_rate' => fn (Blueprint $table) => $table->decimal('tax_rate', 7, 4)->nullable()->after('unit_price'),
            'tax_amount' => fn (Blueprint $table) => $table->decimal('tax_amount', 12, 2)->nullable()->after('tax_rate'),
            'effective_from' => fn (Blueprint $table) => $table->dateTime('effective_from')->nullable()->after('tax_amount'),
            'effective_to' => fn (Blueprint $table) => $table->dateTime('effective_to')->nullable()->after('effective_from'),
            'metadata' => fn (Blueprint $table) => $table->json('metadata')->nullable()->after('effective_to'),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('contract_charges', $name)) {
                Schema::table('contract_charges', $definition);
            }
        }

        $hasAmendmentForeignKey = collect(Schema::getForeignKeys('contract_charges'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['amendment_id']);
        if (! $hasAmendmentForeignKey) {
            Schema::table('contract_charges', function (Blueprint $table): void {
                $table->foreign('amendment_id')->references('id')->on('contract_amendments')->restrictOnDelete();
            });
        }

        if (! Schema::hasIndex('contract_charges', 'contract_charges_contract_source_idx')) {
            Schema::table('contract_charges', function (Blueprint $table): void {
                $table->index(['contract_id', 'source_type'], 'contract_charges_contract_source_idx');
            });
        }

        DB::table('contract_charges')->whereNull('source_type')->update(['source_type' => 'original']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('contract_charges', 'amendment_id')) {
            return;
        }

        if (Schema::hasIndex('contract_charges', 'contract_charges_contract_source_idx')) {
            Schema::table('contract_charges', fn (Blueprint $table) => $table->dropIndex('contract_charges_contract_source_idx'));
        }

        $hasAmendmentForeignKey = collect(Schema::getForeignKeys('contract_charges'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['amendment_id']);
        if ($hasAmendmentForeignKey) {
            Schema::table('contract_charges', fn (Blueprint $table) => $table->dropForeign(['amendment_id']));
        }

        $columns = array_values(array_filter(
            ['amendment_id', 'source_type', 'quantity', 'unit', 'unit_price', 'tax_rate', 'tax_amount', 'effective_from', 'effective_to', 'metadata'],
            fn (string $column): bool => Schema::hasColumn('contract_charges', $column)
        ));
        if ($columns !== []) {
            Schema::table('contract_charges', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
