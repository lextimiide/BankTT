<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\CompteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 Routes
Route::prefix('v1')->middleware(['api'])->group(function () {

    // Routes des comptes bancaires
    Route::prefix('comptes')->middleware(['throttle:api'])->group(function () {
        Route::get('/', [CompteController::class, 'index'])
            ->name('comptes.index')
            ->middleware('App\Http\Middleware\RatingMiddleware');

        Route::get('/{id}', [CompteController::class, 'show'])
            ->name('comptes.show')
            ->middleware('App\Http\Middleware\RatingMiddleware');

        Route::put('/{id}', [CompteController::class, 'update'])
            ->name('comptes.update')
            ->middleware('App\Http\Middleware\LoggingMiddleware');

        Route::delete('/{id}', [CompteController::class, 'destroy'])
            ->name('comptes.destroy')
            ->middleware('App\Http\Middleware\LoggingMiddleware');

        Route::post('/', [CompteController::class, 'store'])
            ->name('comptes.store')
            ->middleware('App\Http\Middleware\LoggingMiddleware');
    });
});
