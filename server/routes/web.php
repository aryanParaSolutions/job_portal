<?php

use App\Http\Controllers\Admin\DogServiceController;
use App\Http\Controllers\Admin\WalkDurationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->middleware('auth:web')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.pages.index');
    })->middleware(['auth:web'])->name('admin.dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('dog-services', [DogServiceController::class, 'index'])->name('dog-services.index');
    Route::get('dog-services/create', [DogServiceController::class, 'create'])->name('dog-services.create');
    Route::post('dog-services', [DogServiceController::class, 'store'])->name('dog-services.store');
    Route::get('dog-services/{id}/edit', [DogServiceController::class, 'edit'])->name('dog-services.edit');
    Route::post('dog-services/{id}/update', [DogServiceController::class, 'update'])->name('dog-services.update');
    Route::get('dog-services/{id}/delete', [DogServiceController::class, 'destroy'])->name('dog-services.destroy');

    Route::get('walk-duration', [WalkDurationController::class, 'index'])->name('walk-duration.index');
    Route::get('walk-duration/create', [WalkDurationController::class, 'create'])->name('walk-duration.create');
    Route::post('walk-duration', [WalkDurationController::class, 'store'])->name('walk-duration.store');
    Route::get('walk-duration/{id}/edit', [WalkDurationController::class, 'edit'])->name('walk-duration.edit');
    Route::post('walk-duration/{id}/update', [WalkDurationController::class, 'update'])->name('walk-duration.update');
    Route::get('walk-duration/{id}/delete', [WalkDurationController::class, 'destroy'])->name('walk-duration.destroy');

});


require __DIR__ . '/auth.php';
