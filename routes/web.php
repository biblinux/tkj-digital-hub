<?php

use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/activate/{token}', [AccountActivationController::class, 'show'])
    ->middleware('throttle:10,1')
    ->name('activation.show');

Route::post('/activate/{token}', [AccountActivationController::class, 'activate'])
    ->middleware('throttle:5,1')
    ->name('activation.activate');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin-test', function () {
        return 'Akses admin berhasil.';
    })->middleware('permission:manage_users');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('/admin/users', [UserController::class, 'index'])
         ->name('admin.users.index');

    Route::get('/admin/users/create', [UserController::class, 'create'])
         ->name('admin.users.create');

    Route::post('/admin/users', [UserController::class, 'store'])
         ->name('admin.users.store');
});

