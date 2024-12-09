<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Pages\Panel\Admin\Dashboard;


// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');

