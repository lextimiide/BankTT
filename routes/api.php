<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;

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
            ->middleware('throttle:100,1'); // Temporairement augmenté pour tests

        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('auth.refresh')
            ->middleware(['auth.api', 'throttle:api']);

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout')
            ->middleware(['auth.api', 'throttle:api']);
    });

    // Routes des comptes bancaires (avec authentification et autorisation)
    Route::prefix('comptes')->middleware(['throttle:api'])->group(function () {
        // Routes nécessitant une authentification Admin pour création
        Route::middleware(['auth.api'])->group(function () {
            Route::post('/', [CompteController::class, 'store'])
                ->name('comptes.store')
                ->middleware(['role:admin', 'App\Http\Middleware\LoggingMiddleware']);
        });

        // Routes nécessitant une authentification
        Route::middleware(['auth.api'])->group(function () {
            // Routes pour tous les utilisateurs authentifiés
            Route::get('/', [CompteController::class, 'index'])
                ->name('comptes.index')
                ->middleware('App\Http\Middleware\RatingMiddleware');

            // Récupération par numéro de compte
            Route::get('/numero/{numero}', [CompteController::class, 'showByNumero'])
                ->name('comptes.show.by.numero')
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

    // Routes des clients (avec authentification et autorisation)
    Route::prefix('clients')->middleware(['throttle:api'])->group(function () {
        Route::middleware(['auth.api'])->group(function () {
            // Recherche de client par téléphone ou NCI
            Route::get('/recherche', [ClientController::class, 'search'])
                ->name('clients.search')
                ->middleware('App\Http\Middleware\RatingMiddleware');
        });
    });
});
