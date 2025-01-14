<?php

use App\Http\Controllers\CarReservationController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Pages\Panel\Expert\Dashboard;
use App\Livewire\Pages\Panel\Auth\Login;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestDetail;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestEdit;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestList;

Route::middleware(['auth.check'])->group(function () {
    Route::get('/expert/dashboard', Dashboard::class)->name('expert.dashboard');
    Route::get('/expert/rental-requests', RentalRequestList::class)->name('rental-requests.list');
    Route::get('/expert/rental-requests/detail/{contractId}', RentalRequestDetail::class)->name('rental-requests.details');
    Route::get('/expert/rental-requests/edit/{contractId}', RentalRequestEdit::class)->name('rental-requests.edit');
});





Route::get('/auth/login', Login::class)->name('auth.login')->middleware('auth.guest');







// use App\Imports\CarImport;
// use Maatwebsite\Excel\Facades\Excel;
// Route::get('/import-cars', function () {
//     // Masir file Excel ro moshakhas mikonid
//     $filePath = storage_path('app/private/Cars.xlsx');
//     // Import file Excel
//     Excel::import(new CarImport, $filePath);

//     // Return success message
//     return 'Data has been imported successfully from the given file.';
// });


use App\Livewire\Reservation\ReserveCarForm;

Route::get('/reservations', ReserveCarForm::class);
Route::post('/reserve-car', [CarReservationController::class, 'reserveCar'])->name('reserve.car');
