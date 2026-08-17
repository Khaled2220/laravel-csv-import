<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // CSV Imports
    Route::get('/imports', [ImportController::class, 'index'])
        ->name('imports.index');

    Route::post('/imports', [ImportController::class, 'store'])
        ->name('imports.store');

    Route::get('/imports/history', [ImportController::class, 'history'])
        ->name('imports.history');

    Route::get('/imports/{import}', [ImportController::class, 'show'])
        ->name('imports.show');
});

require __DIR__.'/auth.php';