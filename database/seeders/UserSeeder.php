<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // افزودن کاربر اول
        User::create([
            'first_name' => 'Ali',
            'last_name' => 'Balash',
            'email' => 'ali@example.com',
            'phone' => '09123456789',
            'avatar' => 'path/to/avatar1.jpg',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'last_login' => now(),
            'national_code' => '1234567890',
            'address' => 'Tehran, Iran',
        ]);

        // افزودن کاربر دوم
        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '09111223344',
            'avatar' => 'path/to/avatar2.jpg',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
            'last_login' => now()->subDays(2),
            'national_code' => '0987654321',
            'address' => 'New York, USA',
        ]);

        
    }
}
