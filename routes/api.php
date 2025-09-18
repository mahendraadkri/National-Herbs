<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [UserController::class, 'register']); 
Route::post('login',    [UserController::class, 'login']);
Route::post('storecontact', [ContactUsController::class, 'store']);

Route::resource('categories', CategoryController::class)->only(['index', 'show']);
Route::resource('products', ProductController::class)->only(['index', 'show']);
// Route::post('products/{product}', [ProductController::class, 'update']);
Route::resource('users', UserController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['store', 'update', 'destroy']);
    Route::get('viewcontact', [ContactUsController::class, 'index']);
    Route::resource('users', UserController::class);
});