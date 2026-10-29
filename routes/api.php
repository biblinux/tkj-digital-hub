<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ItReportController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SuggestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 untuk frontend statis (prefix /api/v1)
|--------------------------------------------------------------------------
| Autentikasi memakai token Bearer, bukan cookie, sehingga tidak ada
| CSRF dan CORS tidak perlu mengizinkan kredensial.
*/
Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/meta', MetaController::class)->middleware('throttle:60,1')->name('meta');
    Route::get('/content', ContentController::class)->middleware('throttle:60,1')->name('content');

    Route::post('/suggestions', [SuggestionController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('suggestions.store');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware(['api.auth', 'throttle:60,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1,profile')->name('profile.update');
        Route::post('/profile/photo', [ProfileController::class, 'photo'])->middleware('throttle:10,1,profile')->name('profile.photo');
        Route::post('/profile/photo/remove', [ProfileController::class, 'removePhoto'])->middleware('throttle:10,1,profile')->name('profile.photo.remove');

        Route::get('/projects/mine', [ProjectController::class, 'mine'])->name('projects.mine');
        Route::post('/projects', [ProjectController::class, 'store'])->middleware('throttle:8,60,project-create')->name('projects.store');
        Route::post('/projects/{project}', [ProjectController::class, 'update'])->whereNumber('project')->middleware('throttle:30,60,project-edit')->name('projects.update');
        Route::post('/projects/{project}/delete', [ProjectController::class, 'destroy'])->whereNumber('project')->middleware('throttle:30,60,project-edit')->name('projects.destroy');

        Route::get('/reports', [ItReportController::class, 'index'])->name('reports.index');
        Route::post('/reports', [ItReportController::class, 'store'])
            ->middleware('throttle:10,60')
            ->name('reports.store');
        Route::get('/reports/{report}', [ItReportController::class, 'show'])
            ->whereNumber('report')
            ->name('reports.show');
        Route::post('/reports/{report}/comments', [ItReportController::class, 'comment'])
            ->whereNumber('report')
            ->middleware('throttle:20,10')
            ->name('reports.comment');
    });
});
