<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;

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
    Route::prefix('auth')->name('auth.')->middleware('throttle:5,1')->group(function () {
        Route::post('register', [RegistrationController::class, 'register'])->name('register');
        Route::post('login', [LoginController::class, 'login'])->name('login');
        Route::post('refresh', [TokenController::class, 'refresh'])->name('refresh');
    });

    /**
     * User Management Endpoints
     *
     * Protected routes for authenticated users to manage their profiles.
     * Requires a valid JWT token for access (jwt.auth middleware).
     * Moderate rate limiting to prevent abuse while allowing normal usage.
     */
    Route::middleware(['jwt.auth', 'throttle:10,1'])->prefix('user')->name('user.')->group(function () {
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::get('/', [UserController::class, 'me'])->name('me');
        Route::put('/update', [UserController::class, 'update'])->name('update');
        Route::post('/search', [UserController::class, 'search'])->name('search');
    });
    /**
     * User Preferences Endpoints
     *
     * Protected routes for managing user preferences such as application language.
     * Requires a valid JWT token for access.
     * Stricter rate limiting for preference changes.
     */
    Route::middleware(['jwt.auth', 'throttle:10,1'])->prefix('language')->name('language.')->group(function () {
        Route::get('{locale}', [UserController::class, 'updateLanguage'])->name('update');
    });

    /**
     * Administrative Endpoints
     *
     * Restricted routes for system administrators.
     * Requires both JWT authentication and admin role authorization.
     * Standard rate limiting for admin functions.
     */
    Route::middleware(['jwt.auth', 'role:admin', 'throttle:60,1'])->prefix('admin')->name('admin.')->group(function () {

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
