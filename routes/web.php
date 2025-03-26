<?php

use App\Http\Controllers\CarReservationController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Pages\Panel\Expert\Dashboard;
use App\Livewire\Pages\Panel\Auth\Login;
use App\Livewire\Pages\Panel\Expert\Brand\BrandDetail;
use App\Livewire\Pages\Panel\Expert\Brand\BrandForm;
use App\Livewire\Pages\Panel\Expert\Brand\BrandList;
use App\Livewire\Pages\Panel\Expert\Car\CarDetail;
use App\Livewire\Pages\Panel\Expert\Car\CarForm;
use App\Livewire\Pages\Panel\Expert\Car\CarList;
use App\Livewire\Pages\Panel\Expert\Customer\CustomerDetail;
use App\Livewire\Pages\Panel\Expert\Customer\CustomerDocumentUpload;
use App\Livewire\Pages\Panel\Expert\Customer\CustomerHistory;
use App\Livewire\Pages\Panel\Expert\Customer\CustomerList;
use App\Livewire\Pages\Panel\Expert\DiscountCode\DiscountCodeList;
use App\Livewire\Pages\Panel\Expert\Insurances\InsurancesForm;
use App\Livewire\Pages\Panel\Expert\Insurances\InsurancesList;
use App\Livewire\Pages\Panel\Expert\Profile\Profile;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestAgreement;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestAgreementInspection;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestDetail;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestForm;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestHistory;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestList;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestMe;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPayment;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPickupDocument;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestReserved;

Route::middleware(['auth.check'])->group(function () {
    Route::get('/expert/dashboard', Dashboard::class)->name('expert.dashboard');




    Route::get('/expert/rental-requests/list', RentalRequestList::class)->name('rental-requests.list');
    Route::get('/expert/rental-requests/form/{contractId?}', RentalRequestForm::class)->name('rental-requests.form');
    Route::get('/expert/rental-requests/me', RentalRequestMe::class)->name('rental-requests.me');
    Route::get('/expert/rental-requests/detail/{contractId}', RentalRequestDetail::class)->name('rental-requests.details');
    Route::get('/expert/rental-requests/history/{contractId}', RentalRequestHistory::class)->name('rental-requests.history');
    Route::get('/expert/rental-requests/payment/{contractId}/{customerId}', RentalRequestPayment::class)->name('rental-requests.payment');
    Route::get('/expert/rental-requests/reserved', RentalRequestReserved::class)->name('rental-requests.reserved');
    Route::get('/expert/rental-requests/pickup-document/{contractId}', RentalRequestPickupDocument::class)->name('rental-requests.pickup-document');

    Route::get('/expert/rental-requests/agreement', RentalRequestAgreement::class)->name('rental-requests.agreement');
    Route::get('/expert/rental-requests/agreement_inspection/{contractId}', RentalRequestAgreementInspection::class)->name('rental-requests.agreement-inspection');


    Route::get('/expert/car/list/', CarList::class)->name('car.list');
    Route::get('/expert/car/detail/{carId}', CarDetail::class)->name('car.detail');
    Route::get('/expert/car/form/{carId?}', CarForm::class)->name('car.form');


    Route::get('/expert/brand/list', BrandList::class)->name('brand.list');
    Route::get('/expert/brand/detail/{brandId}', BrandDetail::class)->name('brand.detail');
    Route::get('/expert/brand/form/{brandId?}', BrandForm::class)->name('brand.form');


    Route::get('/expert/customer/list', CustomerList::class)->name('customer.list');
    Route::get('/expert/customer/detail/{customerId}', CustomerDetail::class)->name('customer.detail');
    Route::get('/expert/customer/history/{customerId}', CustomerHistory::class)->name('customer.history');
    Route::get('/expert/customer/documents/{contractId}/{customerId}', CustomerDocumentUpload::class)->name('customer.documents');


    Route::get('/expert/insurance/list', InsurancesList::class)->name('insurance.list');
    Route::get('/expert/insurance/form/{insuranceId?}', InsurancesForm::class)->name('insurance.form');



    Route::get('/discount-codes', DiscountCodeList::class)->name('discount.codes');


    Route::get('/my-profile', Profile::class)->name('profile.me');

});





Route::get('/auth/login', Login::class)->name('auth.login')->middleware('auth.guest');







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


use App\Livewire\Reservation\ReserveCarForm;

Route::get('/reservations', ReserveCarForm::class);
Route::post('/reserve-car', [CarReservationController::class, 'reserveCar'])->name('reserve.car');
