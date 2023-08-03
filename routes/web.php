<?php

use App\Http\Controllers\InstallationController;
use App\Http\Controllers\AbandonedCartController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//These are the routes use for app installation purpose
Route::prefix('shopify')->group(function() {
    Route::get('auth', [InstallationController::class, 'startInstallation']);
    Route::get('auth/redirect', [InstallationController::class, 'handleRedirect'])->name('app_install_redirect');
    Route::get('auth/complete', [InstallationController::class, 'shopifyAppInstallationCompleted'])->name('app_installion_complete');
});
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('abandoned/carts', [AbandonedCartController::class, 'index'])->name('abandoned/carts');
});
