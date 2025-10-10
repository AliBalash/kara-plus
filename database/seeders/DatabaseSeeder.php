<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\CarSeeder;
use Database\Seeders\ContractSeeder;
use Database\Seeders\FineSeeder; // برای جریمه‌ها
use Database\Seeders\PaymentSeeder; // برای پرداخت‌ها
use Database\Seeders\TollSeeder; // برای عوارض

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // فراخوانی Seederهای مختلف
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,       // Seeder مربوط به کاربران
            // CarModelSeeder::class,    // Seeder مربوط به مدل‌های خودرو
            // CarSeeder::class,        // Seeder مربوط به خودروها
            // CustomerSeeder::class,    // Seeder مربوط به مشتریان
            // ContractSeeder::class,   // Seeder مربوط به قراردادها
            // FineSeeder::class,       // Seeder مربوط به جریمه‌ها
            // PaymentSeeder::class,    // Seeder مربوط به پرداخت‌ها
            // TollSeeder::class,       // Seeder مربوط به عوارض
        ]);
    }
}
