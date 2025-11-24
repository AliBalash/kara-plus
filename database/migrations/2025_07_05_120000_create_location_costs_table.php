<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_costs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('location')->unique();
            $table->decimal('under_3_fee', 10, 2)->default(0);
            $table->decimal('over_3_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('location_costs')->insert([
            ['location' => 'UAE/Dubai/Clock Tower/Main Branch', 'under_3_fee' => 0, 'over_3_fee' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Downtown', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Deira', 'under_3_fee' => 45, 'over_3_fee' => 45, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Dubai Airport/Terminal 1', 'under_3_fee' => 50, 'over_3_fee' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Dubai Airport/Terminal 2', 'under_3_fee' => 50, 'over_3_fee' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Dubai Airport/Terminal 3', 'under_3_fee' => 50, 'over_3_fee' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Al Maktoum Airport', 'under_3_fee' => 190, 'over_3_fee' => 190, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Jumeirah 1, 2, 3', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/JBR', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Marina', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/JLT', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/JVC', 'under_3_fee' => 60, 'over_3_fee' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Al Barsha', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Business Bay', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Sheikh Zayed Road', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Mohammad Bin Zayed Road', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Damac Hills', 'under_3_fee' => 60, 'over_3_fee' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Damac Hills 2', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Arjan', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Al Warqa', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Creek Harbour', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Ras Al Khor', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Al Quoz', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Al Qusais', 'under_3_fee' => 50, 'over_3_fee' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Global Village', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Miracle Garden', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Palm', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Jebel Ali – Ibn Battuta – Hatta & more', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Dubai/Hatta', 'under_3_fee' => 150, 'over_3_fee' => 150, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Sharjah Airport', 'under_3_fee' => 70, 'over_3_fee' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Ajman', 'under_3_fee' => 100, 'over_3_fee' => 100, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'UAE/Abu Dhabi Airport', 'under_3_fee' => 200, 'over_3_fee' => 200, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('location_costs');
    }
};
