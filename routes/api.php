<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AuthController;

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

    // Routes d'authentification (sans authentification requise)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->name('auth.login')
            ->middleware('throttle:login');

        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('auth.refresh')
            ->middleware(['auth.api', 'throttle:api']);

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout')
            ->middleware(['auth.api', 'throttle:api']);
    });

    // Routes des comptes bancaires (avec authentification et autorisation)
    Route::prefix('comptes')->middleware(['throttle:api'])->group(function () {
        // Routes publiques (pas d'authentification requise pour certaines actions)
        Route::post('/', [CompteController::class, 'store'])
            ->name('comptes.store')
            ->middleware('App\Http\Middleware\LoggingMiddleware');

        // Routes nécessitant une authentification
        Route::middleware(['auth.api'])->group(function () {
            // Routes pour tous les utilisateurs authentifiés
            Route::get('/', [CompteController::class, 'index'])
                ->name('comptes.index')
                ->middleware('App\Http\Middleware\RatingMiddleware');

            Route::get('/{id}', [CompteController::class, 'show'])
                ->name('comptes.show')
                ->middleware('App\Http\Middleware\RatingMiddleware');

            // Routes nécessitant des permissions spécifiques
            Route::put('/{id}', [CompteController::class, 'update'])
                ->name('comptes.update')
                ->middleware(['role:admin', 'App\Http\Middleware\LoggingMiddleware']);

            Route::delete('/{id}', [CompteController::class, 'destroy'])
                ->name('comptes.destroy')
                ->middleware(['role:admin', 'App\Http\Middleware\LoggingMiddleware']);

            // Routes pour le blocage/déblocage des comptes épargne (Admin seulement)
            Route::post('/{id}/bloquer', [CompteController::class, 'block'])
                ->name('comptes.block')
                ->middleware(['role:admin', 'App\Http\Middleware\LoggingMiddleware']);

            Route::post('/{id}/debloquer', [CompteController::class, 'unblock'])
                ->name('comptes.unblock')
                ->middleware(['role:admin', 'App\Http\Middleware\LoggingMiddleware']);
        });
    });
});
