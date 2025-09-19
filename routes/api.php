<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// User Login & Register API.
Route::post('register', [UserController::class, 'register']); 
Route::post('login',    [UserController::class, 'login']);

// Contact Store or Send API.
Route::post('storecontact', [ContactUsController::class, 'store']);

//View Categories API.
Route::resource('categories', CategoryController::class)->only(['index', 'show']);

// View Products API.
Route::resource('products', ProductController::class)->only(['index', 'show']);

// User Login logout API. 
Route::resource('users', UserController::class)->only(['login', 'logout']);

// Blog API.
Route::resource('blogs', BlogController::class)->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    // Category API.
    Route::resource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);

    // Products API.
    Route::resource('products', ProductController::class)->only(['store', 'update', 'destroy']);

    // Contact View API.
    Route::get('viewcontact', [ContactUsController::class, 'index']);

    // View Contact API.
    Route::resource('users', UserController::class)->only(['show', 'index']);

    // Blog API.
    Route::resource('blogs', BlogController::class)->only(['store', 'update', 'destroy']);
});