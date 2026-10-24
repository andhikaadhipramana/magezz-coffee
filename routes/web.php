<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PublicCartController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StaffOrderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Katalog menu publik (tanpa login) - untuk customer
Route::get('/', [PublicMenuController::class, 'index'])->name('public.menu');

// Keranjang & pesanan customer (tanpa login)
Route::prefix('cart')->name('public.cart.')->group(function () {
    Route::get('/', [PublicCartController::class, 'index'])->name('index');
    Route::get('/count', [PublicCartController::class, 'count'])->name('count');
    Route::post('/add', [PublicCartController::class, 'add'])->name('add');
    Route::post('/update', [PublicCartController::class, 'update'])->name('update');
    Route::post('/remove', [PublicCartController::class, 'remove'])->name('remove');
    Route::post('/clear', [PublicCartController::class, 'clear'])->name('clear');
});

// Cek status pesanan - harus sebelum rute {code} agar tidak tertangkap wildcard
Route::get('/order/cek', [PublicOrderController::class, 'check'])->name('public.order.check');

Route::get('/order/{code}', [PublicOrderController::class, 'show'])->name('public.order.show');
Route::post('/order', [PublicOrderController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.order.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS - semua peran
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/add', [PosController::class, 'add'])->name('add');
        Route::post('/update', [PosController::class, 'update'])->name('update');
        Route::post('/remove', [PosController::class, 'remove'])->name('remove');
        Route::post('/clear', [PosController::class, 'clear'])->name('clear');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    });

    // Transaksi
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::post('/transactions/{transaction}/void', [TransactionController::class, 'void'])
        ->middleware('role:manager')
        ->name('transactions.void');

    // Pesanan online dari customer - semua peran
    Route::get('/orders', [StaffOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/pay', [StaffOrderController::class, 'pay'])->name('orders.pay');
    Route::post('/orders/pay/abort', [StaffOrderController::class, 'payAbort'])->name('orders.pay-abort');
    Route::post('/orders/{order}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.update-status');

    // Shift & Absensi - semua peran
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');

    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::post('/attendances/clock-in', [AttendanceController::class, 'clockIn'])->name('attendances.clock-in');
    Route::post('/attendances/clock-out', [AttendanceController::class, 'clockOut'])->name('attendances.clock-out');
    Route::post('/attendances/correct', [AttendanceController::class, 'correct'])
        ->middleware('role:manager')
        ->name('attendances.correct');

    // Laporan - manager
    Route::middleware('role:manager')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    // Master data - manager
    Route::middleware('role:manager')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
        Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
        Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
    });

    // User management & pengaturan - manager
    Route::middleware('role:manager')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
