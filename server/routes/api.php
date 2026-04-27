<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthPasswordController;
use App\Http\Controllers\API\CandidateAuthController;
use App\Http\Controllers\API\EmployerAuthController;
use App\Http\Controllers\API\EmployerEmailVerificationController;
use App\Http\Controllers\API\CandidateApplicationController;
use App\Http\Controllers\API\CandidateProfileController;
use App\Http\Controllers\API\CandidateResumeController;
use App\Http\Controllers\API\PublicJobController;
use App\Http\Controllers\API\EmployerApplicantController;
use App\Http\Controllers\API\EmployerCompanyController;
use App\Http\Controllers\API\EmployerDashboardController;
use App\Http\Controllers\API\EmployerJobController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:12,1');

    Route::post('/candidate/register', [CandidateAuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/employer/register', [EmployerAuthController::class, 'register'])->middleware('throttle:6,1');

    Route::post('/forgot-password', [AuthPasswordController::class, 'forgotPassword'])->middleware('throttle:6,1');
    Route::post('/reset-password', [AuthPasswordController::class, 'resetPassword'])->middleware('throttle:6,1');

    Route::get('/employer/verify-email/{id}/{hash}', [EmployerEmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('api.auth.employer.verify-email');

    // Route::post('/employer/resend-verification', [EmployerEmailVerificationController::class, 'resend'])
    //     ->middleware(['auth.cookie', 'active', 'role:employer']);

    Route::middleware(['auth.cookie', 'active'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/jobs', [PublicJobController::class, 'index']);
Route::get('/jobs/{slug}', [PublicJobController::class, 'show']);

Route::prefix('admin')->middleware(['auth.cookie', 'active', 'role:admin'])->group(function () {
    Route::get('/ping', fn () => response()->json([
        'status' => true,
        'portal' => 'admin',
        'user' => request()->user()?->only(['id', 'email', 'status']),
    ]));
});

Route::prefix('employer')->middleware(['auth.cookie', 'active', 'role:employer'])->group(function () {
    Route::get('/ping', fn () => response()->json([
        'status' => true,
        'portal' => 'employer',
        'user' => request()->user()?->only(['id', 'email', 'status']),
    ]));

    Route::get('/dashboard', [EmployerDashboardController::class, 'index']);

    Route::get('/company', [EmployerCompanyController::class, 'show']);
    Route::put('/company', [EmployerCompanyController::class, 'update']);

    Route::get('/jobs', [EmployerJobController::class, 'index']);
    Route::post('/jobs', [EmployerJobController::class, 'store']);
    Route::put('/jobs/{job}', [EmployerJobController::class, 'update']);
    Route::post('/jobs/{job}/publish', [EmployerJobController::class, 'publish']);

    Route::get('/jobs/{job}/applicants', [EmployerApplicantController::class, 'listByJob']);
    Route::get('/applications/{application}', [EmployerApplicantController::class, 'show']);
    Route::post('/applications/{application}/shortlist', [EmployerApplicantController::class, 'shortlist']);
    Route::post('/applications/{application}/reject', [EmployerApplicantController::class, 'reject']);
    Route::post('/applications/{application}/interview', [EmployerApplicantController::class, 'interview']);
    Route::get('/applications/{application}/resume', [EmployerApplicantController::class, 'downloadResume']);
});

Route::prefix('candidate')->middleware(['auth.cookie', 'active', 'role:candidate'])->group(function () {
    Route::get('/ping', fn () => response()->json([
        'status' => true,
        'portal' => 'candidate',
        'user' => request()->user()?->only(['id', 'email', 'status']),
    ]));

    Route::get('/profile', [CandidateProfileController::class, 'show']);
    Route::put('/profile', [CandidateProfileController::class, 'update']);

    Route::get('/resume/current', [CandidateResumeController::class, 'current']);
    Route::post('/resume', [CandidateResumeController::class, 'upload']);

    Route::post('/jobs/{job}/apply', [CandidateApplicationController::class, 'apply']);

    Route::get('/applications', [CandidateApplicationController::class, 'index']);
    Route::get('/applications/{application}', [CandidateApplicationController::class, 'show']);
});