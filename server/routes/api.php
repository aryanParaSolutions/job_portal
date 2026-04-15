<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DogController;
use App\Http\Controllers\API\ScheduledServiceController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/login-error', function () {
    return response()->json([
        'status' => false,
        'message' => 'Please login again'
    ], 401);
})->name('api.login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [PasswordResetLinkController::class, 'resetPassword'])->name('password.update');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/user/update', [AuthController::class, 'update']);
    Route::post('user/logout', [AuthController::class, 'logout']);  

    Route::get('/get-breeds', [DogController::class, 'getBreeds']);
    Route::get('/users/dogs', [DogController::class, 'index']);
    Route::post('/dogs', [DogController::class, 'store']);
    Route::post('/dogs/{dogId}/update', [DogController::class, 'update']); 
    Route::get('/dogs/{dogId}/delete', [DogController::class, 'destroy']);

    Route::get('/dog-services', [DogController::class, 'getDogServices']);

    Route::get('/scheduled-services', [ScheduledServiceController::class, 'index']);
    Route::post('/schedule-service', [ScheduledServiceController::class, 'store']);
    Route::post('/scheduled-services/{id}/update', [ScheduledServiceController::class, 'update']); // POST for update
    Route::get('/scheduled-services/{id}/delete', [ScheduledServiceController::class, 'destroy']); // GET for delete


});
