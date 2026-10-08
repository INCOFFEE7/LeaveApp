<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingPageController;
use App\Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Route;

// 🌐 Landing page untuk semua (login atau tidak login)
Route::get('/', [LandingPageController::class, 'index'])->name('landing');

Route::get('/app', fn () => redirect(Dashboard::getUrl(panel: 'main')));

// Backward-compatible dashboard route used by auth redirects and tests
Route::get('/dashboard', fn () => redirect('/app'))->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
