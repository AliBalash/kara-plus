<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_price_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('mode', 16);
            $table->json('payload');
            $table->timestamp('expires_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
        Permission::findOrCreate('manage fleet prices', 'web');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_price_operations');
    }
};
