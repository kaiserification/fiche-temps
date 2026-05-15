<?php

use App\Http\Controllers\Settings\AppController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/application', [AppController::class, 'edit'])->name('app-settings.edit');
    Route::get('settings/directories', [AppController::class, 'listDirectories'])->name('app-settings.directories');
    Route::patch('settings/application', [AppController::class, 'update'])->name('app-settings.update');
    Route::patch('settings/application/user',      [AppController::class, 'updateUser'])->name('app-settings.update-user');
    Route::patch('settings/application/signature', [AppController::class, 'updateSignature'])->name('app-settings.update-signature');
});
