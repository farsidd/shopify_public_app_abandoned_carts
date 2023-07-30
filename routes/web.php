<?php

use App\Http\Controllers\InstallationController;
use Illuminate\Support\Facades\Route;


Route::prefix('shopify')->group(function() {
    Route::get('auth', [InstallationController::class, 'startInstallation']);
    Route::get('auth/redirect', [InstallationController::class, 'handleRedirect'])->name('app_install_redirect');
    Route::get('auth/complete', [InstallationController::class, 'shopifyAppInstallationCompleted'])->name('app_installion_complete');
});
