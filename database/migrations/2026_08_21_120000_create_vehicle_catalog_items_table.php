<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('website_slug', 120)->unique();
            $table->string('display_name');
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->string('match_brand', 100);
            $table->string('match_model', 100);
            $table->unsignedSmallInteger('manufacturing_year');
            $table->string('trim', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['match_brand', 'match_model', 'manufacturing_year'], 'vehicle_catalog_match_index');
        });

        $now = now();
        $items = [
            ['KIA-PIC-22', 'kia-picanto-2022', 'KIA Picanto', 'KIA', 'Picanto', 'KIA', 'PICANTO', 2022, null],
            ['SUZ-CEL-23', 'suzuki-celerio', 'Suzuki Celerio', 'Suzuki', 'Celerio', 'SUZUKI', 'CELERIO', 2023, null],
            ['SUZ-DZR-22', 'suzuki-dzire', 'Suzuki Dzire', 'Suzuki', 'Dzire', 'SUZUKI', 'DZIRE', 2022, null],
            ['MIT-ATT-25', 'mitsubishi-attrage', 'Mitsubishi Attrage', 'Mitsubishi', 'Attrage', 'MITSUBISHI', 'ATTRAGE', 2025, null],
            ['SUZ-BAL-24', 'suzuki-baleno', 'Suzuki Baleno', 'Suzuki', 'Baleno', 'SUZUKI', 'BALENO', 2024, null],
            ['NIS-SUN-24', 'nissan-sunny', 'Nissan Sunny', 'Nissan', 'Sunny', 'NISSAN', 'SUNNY', 2024, null],
            ['SUZ-SWF-24', 'suzuki-swift', 'Suzuki Swift', 'Suzuki', 'Swift', 'SUZUKI', 'SWIFT', 2024, null],
            ['KIA-PEG-24', 'kia-pegas', 'KIA Pegas', 'KIA', 'Pegas', 'KIA', 'PEGAS', 2024, null],
            ['KIA-RIO-22', 'kia-rio', 'KIA Rio', 'KIA', 'Rio', 'KIA', 'RIO', 2022, null],
            ['TOY-RAZ-22', 'toyota-raize', 'Toyota Raize', 'Toyota', 'Raize', 'TOYOTA', 'RAIZE', 2022, null],
            ['TOY-YAR-22', 'toyota-yaris', 'Toyota Yaris', 'Toyota', 'Yaris', 'TOYOTA', 'YARIS', 2022, null],
            ['HYU-ELA-23', 'hyundai-elantra-2023', 'Hyundai Elantra', 'Hyundai', 'Elantra', 'HYUNDAI', 'ELANTRA', 2023, null],
            ['NIS-KIK-23', 'nissan-kicks', 'Nissan Kicks', 'Nissan', 'Kicks', 'NISSAN', 'KICKS', 2023, null],
            ['TOY-COR-22', 'toyota-corolla', 'Toyota Corolla', 'Toyota', 'Corolla', 'TOYOTA', 'COROLLA', 2022, null],
            ['HYU-ELA-24', 'hyundai-elantra', 'Hyundai Elantra', 'Hyundai', 'Elantra', 'HYUNDAI', 'ELANTRA', 2024, null],
            ['SUZ-ERT-23', 'suzuki-ertiga', 'Suzuki Ertiga', 'Suzuki', 'Ertiga', 'SUZUKI', 'ERTIGA', 2023, null],
            ['HYU-ACC-25', 'hyundai-accent', 'Hyundai Accent', 'Hyundai', 'Accent', 'HYUNDAI', 'ACCENT', 2025, null],
            ['TOY-CRLV-24', 'toyota-corolla-levin', 'Toyota Corolla Levin', 'Toyota', 'Corolla Levin', 'TOYOTA', 'COROLLA LEVIN', 2024, null],
            ['KIA-SNT-24', 'kia-sonet', 'KIA Sonet', 'KIA', 'Sonet', 'KIA', 'SONET', 2024, null],
            ['KIA-CER-23', 'kia-cerato', 'KIA Cerato', 'KIA', 'Cerato', 'KIA', 'CERATO', 2023, null],
            ['HYU-CRT-25', 'hyundai-creta', 'Hyundai Creta', 'Hyundai', 'Creta', 'HYUNDAI', 'CRETA', 2025, null],
            ['CHV-GRV-23', 'chevrolet-groove', 'Chevrolet Groove', 'Chevrolet', 'Groove', 'CHEVROLET', 'GROOVE', 2023, null],
            ['KIA-K3-26', 'kia-k3', 'KIA K3', 'KIA', 'K3', 'KIA', 'K3', 2026, null],
            ['CHV-CPT-23', 'chevrolet-captiva', 'Chevrolet Captiva', 'Chevrolet', 'Captiva', 'CHEVROLET', 'CAPTIVA', 2023, null],
            ['MIT-XPD-24', 'mitsubishi-xpander', 'Mitsubishi Xpander', 'Mitsubishi', 'Xpander', 'MITSUBISHI', 'XPANDER', 2024, null],
            ['KIA-SPT-23', 'kia-sportage', 'KIA Sportage', 'KIA', 'Sportage', 'KIA', 'SPORTAGE', 2023, null],
            ['TOY-VLZ-24', 'toyota-veloz', 'Toyota Veloz', 'Toyota', 'Veloz', 'TOYOTA', 'VELOZ', 2024, null],
            ['KIA-SEL-24', 'kia-seltos', 'KIA Seltos', 'KIA', 'Seltos', 'KIA', 'SELTOS', 2024, null],
            ['HYU-SON-23', 'hyundai-sonata-2023', 'Hyundai Sonata', 'Hyundai', 'Sonata', 'HYUNDAI', 'SONATA', 2023, null],
            ['KIA-K5-23', 'kia-k5', 'KIA K5', 'KIA', 'K5', 'KIA', 'K5', 2023, null],
            ['KIA-SPTL-26', 'kia-sportage-long', 'KIA Sportage Long', 'KIA', 'Sportage Long', 'KIA', 'SPORTAGE LONG', 2026, null],
            ['TOY-CRCS-24', 'toyota-corolla-cross', 'Toyota Corolla Cross', 'Toyota', 'Corolla Cross', 'TOYOTA', 'COROLLA CROSS', 2024, null],
            ['HYU-SON-25', 'hyundai-sonata-2025', 'Hyundai Sonata', 'Hyundai', 'Sonata', 'HYUNDAI', 'SONATA', 2025, null],
            ['HYU-TUC-25', 'hyundai-tucson', 'Hyundai Tucson', 'Hyundai', 'Tucson', 'HYUNDAI', 'TUCSON', 2025, null],
            ['KIA-SRN-24', 'kia-sorento', 'KIA Sorento', 'KIA', 'Sorento', 'KIA', 'SORENTO', 2024, null],
            ['MIT-OUT-25', 'mitsubishi-outlander', 'Mitsubishi Outlander', 'Mitsubishi', 'Outlander', 'MITSUBISHI', 'OUTLANDER', 2025, null],
            ['MIT-MON-26', 'mitsubishi-montero-sport', 'Mitsubishi Montero Sport', 'Mitsubishi', 'Montero Sport', 'MITSUBISHI', 'MONTERO SPORT', 2026, null],
            ['MIT-DST-26', 'mitsubishi-destinator-2026', 'Mitsubishi Destinator', 'Mitsubishi', 'Destinator', 'MITSUBISHI', 'DESTINATOR', 2026, null],
            ['JET-T2-25', 'jetour-t2', 'Jetour T2', 'Jetour', 'T2', 'JETOUR', 'T2', 2025, null],
            ['TOY-LCR-25', 'toyota-landcruiser', 'Toyota Land Cruiser', 'Toyota', 'Land Cruiser', 'TOYOTA', 'LANDCRUISER', 2025, null],
            ['NIS-PTR-25', 'nissanpatrol-setitanium-2025', 'Nissan Patrol Titanium', 'Nissan', 'Patrol', 'NISSAN', 'PATROL', 2025, 'Titanium'],
            ['NIS-PTRT-26', 'patrol-setitanium2026', 'Nissan Patrol SE Titanium', 'Nissan', 'Patrol SE Titanium', 'NISSAN', 'PATROL SE TITANIUM', 2026, 'SE Titanium'],
            ['MBZ-ECLE200-26', 'benz-e200', 'Mercedes-Benz E-Class E200', 'Mercedes-Benz', 'E-Class', 'BENZ', 'E200', 2026, 'E200'],
        ];

        DB::table('vehicle_catalog_items')->insert(array_map(static fn (array $item): array => [
            'code' => $item[0], 'website_slug' => $item[1], 'display_name' => $item[2],
            'brand' => $item[3], 'model' => $item[4], 'match_brand' => $item[5],
            'match_model' => $item[6], 'manufacturing_year' => $item[7], 'trim' => $item[8],
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ], $items));
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_catalog_items');
    }
};
