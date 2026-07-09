<?php
// routes/web.php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Finance\AboutIndex;
use App\Livewire\Finance\AccountIndex;
use App\Livewire\Finance\BackupIndex;
use App\Livewire\Finance\ContactIndex;
use App\Livewire\Finance\ReportStats;
use App\Livewire\Finance\TransactionIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 登入頁面（不需要認證）
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
	Route::get('/register', Register::class)->name('register');
	Route::post('/register', [RegisteredUserController::class, 'store'])
        ->name('register.store');
});

// 登出路由（需要認證）
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// 忘記密碼
Route::get('/forgot-password', ForgotPassword::class)
    ->middleware('guest')
    ->name('password.request');

Route::get('/reset-password/{token}', ResetPassword::class)
    ->middleware('guest')
    ->name('password.reset');

// 所有需要認證的路由
Route::middleware(['auth'])->group(function () {
    // 當訪問首頁時，直接加載資產管理頁面
    Route::get('/', AccountIndex::class)->name('finance.accounts');
    Route::get('/finance/transactions', TransactionIndex::class)->name('finance.transactions');
    Route::get('/finance/reports', ReportStats::class)->name('finance.reports');
	Route::get('/finance/backup', BackupIndex::class)->name('finance.backup');
	Route::get('/finance/about', AboutIndex::class)->name('finance.about');
    Route::get('/finance/contact', ContactIndex::class)->name('finance.contact');
});