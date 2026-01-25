<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\SerachController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1.0')->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
  

    Route::middleware('jwt.auth')->group(function () {
         Route::post('logout', [AuthController::class, 'logout']);
        Route::post('contacts/upload', [ContactController::class, 'upload']);
        Route::post('search-number', [SerachController::class, 'searchNumber']);
    });
});
