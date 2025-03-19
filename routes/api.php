<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Auth Routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('register', [AuthController::class, 'register'])->name('register');
        Route::get('login', [LoginController::class, 'login'])->name('login');
        Route::get('refresh', [AuthController::class, 'refresh'])->name('refresh');
    });

    // jwt.auth middleware /user prefix
    Route::middleware('jwt.auth')->prefix('user')->name('user.')->group(function () {
        Route::get('list', [UserController::class, 'index'])->name('list');
        Route::get('show/{id}', [UserController::class, 'show'])->name('show');
        Route::get('me', [UserController::class, 'me'])->name('me');
        Route::get('update', [UserController::class, 'update'])->name('update');
    });

    // jwt.auth middleware /language prefix
    Route::middleware(['jwt.auth'])->prefix('language')->name('language.')->group(function () {
        Route::get('{locale}', [UserController::class, 'updateLanguage'])->name('update');
    });

    // jwt.auth role:admin middleware /admin
    Route::middleware(['jwt.auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('user')->name('users.')->group(function () {
            Route::get('list', [AdminController::class, 'index'])->name('list');
            Route::get('delete/{id}', [AdminController::class, 'destroy'])->name('delete');
            Route::get('online', [AdminController::class, 'online_list'])->name('online_list');
        });
    });


});
