<?php

use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItReportController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SuggestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/
Route::middleware('frontend')->controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/profil', 'profile')->name('pages.profile');
    Route::get('/kompetensi', 'competencies')->name('pages.competencies');
    Route::get('/kepengurusan', 'organization')->name('pages.organization');
    Route::get('/tim-it', 'itTeam')->name('pages.it-team');
    Route::get('/topologi', 'topology')->name('pages.topology');
    Route::get('/galeri', 'gallery')->name('pages.gallery');
    Route::get('/fasilitas', 'facilities')->name('pages.facilities');
    Route::get('/kontak', 'contact')->name('pages.contact');
});

Route::get('/saran', [SuggestionController::class, 'create'])
    ->middleware('frontend')
    ->name('suggestions.create');
Route::post('/saran', [SuggestionController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('suggestions.store');

/*
|--------------------------------------------------------------------------
| Aktivasi akun
|--------------------------------------------------------------------------
*/
Route::get('/activate/{token}', [AccountActivationController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:10,1')
    ->name('activation.show');

Route::post('/activate/{token}', [AccountActivationController::class, 'activate'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:5,1')
    ->name('activation.activate');

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Area pengguna (wajib login dan akun aktif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Laporan masalah IT
    Route::get('/laporan', [ItReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/buat', [ItReportController::class, 'create'])->name('reports.create');
    Route::post('/laporan', [ItReportController::class, 'store'])
        ->middleware('throttle:10,60')
        ->name('reports.store');
    Route::get('/laporan/{report}', [ItReportController::class, 'show'])
        ->whereNumber('report')
        ->name('reports.show');
    Route::post('/laporan/{report}/keterangan', [ItReportController::class, 'comment'])
        ->whereNumber('report')
        ->middleware('throttle:20,10')
        ->name('reports.comment');

    // Penanganan laporan (teknisi dan admin; dicek lagi di policy)
    Route::get('/kelola/laporan', [ItReportController::class, 'staffIndex'])
        ->name('admin.reports.index');
    Route::post('/laporan/{report}/tangani', [ItReportController::class, 'handle'])
        ->whereNumber('report')
        ->name('reports.handle');

    // Pengelolaan saran
    Route::get('/kelola/saran', [SuggestionController::class, 'index'])
        ->name('admin.suggestions.index');
    Route::patch('/kelola/saran/{suggestion}', [SuggestionController::class, 'update'])
        ->whereNumber('suggestion')
        ->name('admin.suggestions.update');

    // Manajemen pengguna: otorisasi lewat UserPolicy di controller,
    // ditambah middleware izin sebagai lapisan pertama.
    Route::middleware('permission:manage_users')->prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->whereNumber('user')->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->whereNumber('user')->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->whereNumber('user')->name('update');
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->whereNumber('user')->name('deactivate');
        Route::post('/{user}/reactivate', [UserController::class, 'reactivate'])->whereNumber('user')->name('reactivate');
        Route::post('/{user}/resend-activation', [UserController::class, 'resendActivation'])->whereNumber('user')->name('resend');
        Route::put('/{user}/permissions', [UserController::class, 'updatePermissions'])->whereNumber('user')->name('permissions');
    });
});
