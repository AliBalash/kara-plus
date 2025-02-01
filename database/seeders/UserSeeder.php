<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        // User::create([
        //     'first_name' => 'Ali',
        //     'last_name' => 'Balash',
        //     'email' => 'ali@example.com',
        //     'phone' => '09123456789',
        //     'avatar' => 'path/to/avatar1.jpg',
        //     'password' => Hash::make('password123'),
        //     'status' => 'active',
        //     'last_login' => now(),
        //     'national_code' => '1234567890',
        //     'address' => 'Tehran, Iran',
        // ]);

        $users = [
            [
                'first_name' => 'Peter',
                'last_name' => 'Alfy',
                'email' => 'peter.alfy@example.com',
                'phone' => '0971544500681',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Marziyeh',
                'last_name' => 'Kabganian',
                'email' => 'marziyeh.kabganian@example.com',
                'phone' => '0971543100841',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Kiana',
                'last_name' => 'Kabganian',
                'email' => 'kiana.kabganian@example.com',
                'phone' => '0989172671308',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Alireza',
                'last_name' => 'Bakhshi',
                'email' => 'alireza.bakhshi@example.com',
                'phone' => '0971506990563',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Foad',
                'last_name' => 'Farah Bakhsh',
                'email' => 'foad.bakhsh@example.com',
                'phone' => '0971568112902',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Javaid',
                'last_name' => 'Bilal Hussain',
                'email' => 'javaid.hussain@example.com',
                'phone' => '0971508508045',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Ahmed Magdy',
                'last_name' => 'Hassen Garib',
                'email' => 'ahmed.garib@example.com',
                'phone' => '0971506990145',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Raja Ehtesham',
                'last_name' => 'Muhammad Inzad Khan',
                'email' => 'raja.khan@example.com',
                'phone' => '0971501652776',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Muhammad Sheraz',
                'last_name' => 'Muhammad Iqbal',
                'email' => 'muhammad.iqbal@example.com',
                'phone' => '0971503662977',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Sharareh',
                'last_name' => 'Bakhshi',
                'email' => 'sharareh.bakhshi@example.com',
                'phone' => '0989107600030',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Fatemeh',
                'last_name' => 'Shabani',
                'email' => 'fatemeh.shabani@example.com',
                'phone' => '0989203800428',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Solmaz',
                'last_name' => 'Shiri',
                'email' => 'solmaz.shiri@example.com',
                'phone' => '0989339721302',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Mohammad Reza',
                'last_name' => 'Bakhshi',
                'email' => 'mohammad.bakhshi@example.com',
                'phone' => '0989104387828',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Amin',
                'last_name' => 'Salimi',
                'email' => 'amin.salimi@example.com',
                'phone' => '0989385734333',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Omid',
                'last_name' => 'Samadi',
                'email' => 'omid.samadi@example.com',
                'phone' => '0989120420476',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Ali',
                'last_name' => 'Alivar',
                'email' => 'ali.alivar@example.com',
                'phone' => '0989356125442',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Elaheh',
                'last_name' => 'Dezfouli',
                'email' => 'elaheh.dezfouli@example.com',
                'phone' => '0971507655090',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'first_name' => 'Omid',
                'last_name' => 'Mardan',
                'email' => 'omid.mardan@example.com',
                'phone' => '0971568181193',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
        ];

        // ورود داده‌ها به جدول
        DB::table('users')->insert($users);
    }
}
