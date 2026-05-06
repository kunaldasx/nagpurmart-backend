<?php
    // Ensure helper functions are always loaded regardless of the working directory
    include __DIR__ . '/functions.php';

use Illuminate\Support\Facades\Route;
use Froiden\LaravelInstaller\Controllers\WelcomeController;
use Froiden\LaravelInstaller\Controllers\EnvironmentController;
use Froiden\LaravelInstaller\Controllers\RequirementsController;
use Froiden\LaravelInstaller\Controllers\PermissionsController;
use Froiden\LaravelInstaller\Controllers\DatabaseController;
use Froiden\LaravelInstaller\Controllers\FinalController;
use Froiden\LaravelInstaller\Controllers\SuperAdminController;
use Froiden\LaravelInstaller\Controllers\LicenseController;
Route::group(['prefix' => 'install', 'as' => 'LaravelInstaller::', 'middleware' => ['web', 'install']], function()
{
    Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');

        // New License step (separate page before environment)
        Route::get('license', [LicenseController::class, 'form'])->name('license');
        Route::get('license/verify', [LicenseController::class, 'verify'])->name('license.verify');

        Route::get('environment', [EnvironmentController::class, 'environment'])->name('environment');

        Route::get('environment/save', [EnvironmentController::class, 'save'])->name('environmentSave');

        // Super Admin creation step (after environment)
        Route::get('super-admin', [SuperAdminController::class, 'form'])->name('superAdmin');
        // Use GET to capture details via query params per requirement
        Route::get('super-admin/save', [SuperAdminController::class, 'store'])->name('superAdmin.store');

        Route::get('requirements', [RequirementsController::class, 'requirements'])->name('requirements');

        Route::get('permissions', [PermissionsController::class, 'permissions'])->name('permissions');

        Route::get('database', [DatabaseController::class, 'database'])->name('database');

        Route::get('final', [FinalController::class, 'finish'])->name('final');

});
