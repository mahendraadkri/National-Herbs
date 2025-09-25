<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\DistributorController;
use App\Http\Controllers\OurteamController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


// User Login & Register API.
Route::post('login',    [UserController::class, 'login'])->name('users.login');

// Contact Store.
Route::post('storecontact', [ContactUsController::class, 'store'])->name('contacts.store');

//View Categories API.
Route::resource('categories', CategoryController::class)->only(['index', 'show']);

// View Products API.
Route::resource('products', ProductController::class)->only(['index', 'show']);

// User Login logout API. 
Route::resource('users', UserController::class)->only(['login', 'logout']);

// Blog API.
Route::resource('blogs', BlogController::class)->only(['index', 'show']);

// Distributors API
Route::resource('distributors', DistributorController::class)->only(['index', 'show']);

// Our Team API
Route::resource('ourteams', OurteamController::class)->only(['index', 'show']);

// Count Total Products
Route::get('totalproducts', [ProductController::class, 'product_count'])->name('products.count');

// Count Total OurTeam
Route::get('totalteams', [OurteamController::class, 'team_count'])->name('teams.count');

// Count Total Categories
Route::get('totalcategories',[CategoryController::class, 'category_count'])->name('categories.count');

// Count Total Blods
Route::get('totalblogs',[BlogController::class, 'blog_count'])->name('blogs.count');

// Count Total Distributors
Route::get('totaldistributors',[DistributorController::class, 'distributor_count'])->name('distributors.count');

Route::middleware('auth:sanctum')->group(function () {

    // User API.
    Route::get('/me',       [UserController::class, 'me']);
    Route::post('/logout',  [UserController::class, 'logout']);

    // Category API.
    Route::resource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);

    // Products API.
    Route::resource('products', ProductController::class)->only(['store', 'destroy']);
    Route::post('products/{id}', [ProductController::class, 'update'])->name('products.update');

    // Contact API.
    Route::resource('contact-us', ContactUsController::class)->only(['index','destroy']);


    // User API.
    Route::resource('users', UserController::class)->only(['show', 'index']);

    // Blog API.
    Route::resource('blogs', BlogController::class)->only(['store', 'destroy']);
    Route::post('blogs/{id}', [BlogController::class, 'update'])->name('blogs.update');

    // Distributors API.
    Route::resource('distributors', DistributorController::class)->only(['store', 'update', 'destroy']);

    // Our Team API.
    Route::resource('ourteams', OurteamController::class)->only(['store', 'update', 'destroy']);
    Route::post('ourteams/{id}', [OurteamController::class, 'update'])->name('ourteams.update');
});