<?php

use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\CustomerOrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('BerandaView', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/dashboard/{aksi}', [StaffDashboardController::class, 'simpan'])
        ->name('dashboard.simpan');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1,profile-update')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:6,1,profile-delete')->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/layanan', [OnlineOrderController::class, 'index'])->name('services.index');
    Route::post('/layanan/pesanan', [OnlineOrderController::class, 'store'])->name('orders.store');
    Route::get('/pesanan-saya', [CustomerOrderController::class, 'index'])->name('orders.mine');
});

require __DIR__.'/auth.php';