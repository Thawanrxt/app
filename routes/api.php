<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DiseaseTrackingActivityController;
use App\Http\Controllers\Api\FertilizerTrackingActivityController;
use App\Http\Controllers\Api\HarvestTrackingActivityController;
use App\Http\Controllers\Api\MillTrackingActivityController;
use App\Http\Controllers\Api\PestTrackingActivityController;
use App\Http\Controllers\Api\PrepTrackingActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WaterTrackingActivityController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('api.token')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    Route::get('/dashboard', [DashboardController::class, 'show']);

    Route::get('/tracking/prep', [PrepTrackingActivityController::class, 'index']);
    Route::post('/tracking/prep', [PrepTrackingActivityController::class, 'store']);

    Route::get('/tracking/water', [WaterTrackingActivityController::class, 'index']);
    Route::post('/tracking/water', [WaterTrackingActivityController::class, 'store']);

    Route::get('/tracking/fertilizer', [FertilizerTrackingActivityController::class, 'index']);
    Route::post('/tracking/fertilizer', [FertilizerTrackingActivityController::class, 'store']);

    Route::get('/tracking/pest', [PestTrackingActivityController::class, 'index']);
    Route::post('/tracking/pest', [PestTrackingActivityController::class, 'store']);

    Route::get('/tracking/disease', [DiseaseTrackingActivityController::class, 'index']);
    Route::post('/tracking/disease', [DiseaseTrackingActivityController::class, 'store']);

    Route::get('/tracking/harvest', [HarvestTrackingActivityController::class, 'index']);
    Route::post('/tracking/harvest', [HarvestTrackingActivityController::class, 'store']);

    Route::get('/tracking/mill', [MillTrackingActivityController::class, 'index']);
    Route::post('/tracking/mill', [MillTrackingActivityController::class, 'store']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
});
