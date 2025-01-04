<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Pages\Panel\Admin\Dashboard;
use App\Livewire\Pages\Panel\Auth\Login;


// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');

Route::get('/auth/login', Login::class)->name('auth.login');


use App\Livewire\Reservation\ReserveCarForm;



Route::get('/reservations', ReserveCarForm::class);


use App\Imports\CarImport;
use Maatwebsite\Excel\Facades\Excel;
Route::get('/import-cars', function () {
    // Masir file Excel ro moshakhas mikonid
    $filePath = storage_path('app/private/Cars.xlsx');
    // Import file Excel
    Excel::import(new CarImport, $filePath);

    // Return success message
    return 'Data has been imported successfully from the given file.';
});
