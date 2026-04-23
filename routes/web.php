<?php

use App\Http\Controllers\{FicheController, DayEntryController, ExportController};
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/',               [FicheController::class, 'index'])->name('fiche.index');
    Route::get('/fiche/creer',    [FicheController::class, 'create'])->name('fiche.create');
    Route::post('/fiche',         [FicheController::class, 'store'])->name('fiche.store');
    Route::get('/fiche/{fiche}',    [FicheController::class, 'show'])->name('fiche.show');
    Route::patch('/fiche/{fiche}',  [FicheController::class, 'update'])->name('fiche.update');
    Route::delete('/fiche/{fiche}', [FicheController::class, 'destroy'])->name('fiche.destroy');

    Route::post('/fiche/{fiche}/day',        [DayEntryController::class, 'store'])->name('day.store');
    Route::put('/fiche/{fiche}/day/{entry}', [DayEntryController::class, 'update'])->name('day.update');

    Route::get('/fiche/{fiche}/export', [ExportController::class, 'export'])->name('fiche.export');
});
