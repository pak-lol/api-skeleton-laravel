<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\Auth\PasswordRemindController;

/*
|--------------------------------------------------------------------------
| API Routes Configuration
|--------------------------------------------------------------------------
|
| This file defines all API endpoint routes for the application.
| Routes are organized by version, domain functionality, and access control.
| Each route is named for easier reference in generated URLs and logging.
|
*/

/**
 * API Version 1
 *
 * All routes are prefixed with '/api/v1' and named with 'api.v1.' prefix
 * to maintain consistent versioning throughout the application.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {

    /**
     * Authentication Endpoints
     *
     * Handles user registration, authentication, and token management.
     * These routes are publicly accessible without authentication.
     * Strict rate limiting is applied to prevent brute force attacks.
     */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [RegistrationController::class, 'register'])->name('register');
        Route::post('login', [LoginController::class, 'login'])->name('login');
        Route::post('refresh', [TokenController::class, 'refresh'])->name('refresh');
        Route::post('remind', [PasswordRemindController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::post('reset-password', [PasswordRemindController::class, 'reset'])->name('password.reset');
        Route::post('check-token', [PasswordRemindController::class, 'checkToken'])->name('password.check');
        // No refresh token needed with Sanctum as it uses single tokens
        // But we'll add a logout endpoint
        Route::middleware('auth:sanctum')->post('logout', [LoginController::class, 'logout'])->name('logout');
    });

    /**
     * User Management Endpoints
     *
     * Protected routes for authenticated users to manage their profiles.
     * Requires a valid Sanctum token for access.
     * Moderate rate limiting to prevent abuse while allowing normal usage.
     */
    Route::middleware(['auth:sanctum'])->prefix('user')->name('user.')->group(function () {
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::get('/', [UserController::class, 'me'])->name('me');
        Route::put('/update', [UserController::class, 'update'])->name('update');
        Route::post('/search', [UserController::class, 'search'])->name('search');
    });

    /**
     * User Preferences Endpoints
     *
     * Protected routes for managing user preferences such as application language.
     * Requires a valid Sanctum token for access.
     * Stricter rate limiting for preference changes.
     */
    Route::middleware(['auth:sanctum'])->prefix('language')->name('language.')->group(function () {
        Route::get('{locale}', [UserController::class, 'updateLanguage'])->name('update');
    });

    /**
     * Administrative Endpoints
     *
     * Restricted routes for system administrators.
     * Requires both Sanctum authentication and admin role authorization.
     * Standard rate limiting for admin functions.
     */
    Route::middleware(['auth:sanctum', 'ability:admin', 'throttle:60,1'])->prefix('admin')->name('admin.')->group(function () {

        /**
         * Admin User Management
         *
         * Administrative operations for user accounts.
         */
        Route::prefix('user')->name('users.')->group(function () {
            Route::get('/list', [AdminController::class, 'index'])->name('list');
            Route::delete('/delete/{id}', [AdminController::class, 'destroy'])->name('delete');
            Route::get('/view/{id}', [AdminController::class, 'show'])->name('show');
            Route::get('/online', [AdminController::class, 'online_list'])->name('online_list');
        });
    });
});
