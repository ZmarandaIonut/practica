<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Auth\VerificationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\VerifyEmailController;

Auth::routes(["verify" => true]);

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'sendVerifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verify/resend', [VerifyEmailController::class, 'resendEmailVerification'])
      ->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');


Route::post("/forgotPassword", [UserController::class, "forgotPassword"]);
Route::post("/resetPassword/{token}", [UserController::class, "resetPassword"]);

Route::middleware(['auth:sanctum', "verified"])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/categories', [CategoryController::class, 'getAll']);
    Route::post('/category', [CategoryController::class, 'add']);
    Route::get('/category/{id}', [CategoryController::class, 'get']);
    Route::put('/category/{id}', [CategoryController::class, 'update']);
    Route::delete('/category/{id}', [CategoryController::class, 'delete']);
    Route::get('categories-tree', [CategoryController::class, 'tree']);

    Route::get('/products', [ProductController::class, 'getAll']);
    Route::get("/product/{id}", [ProductController::class, "get"]);
    Route::post("/product", [ProductController::class, "add"]);
    Route::put("/product/{id}", [ProductController::class, "update"]);
    Route::delete("/product/{id}", [ProductController::class, "delete"]);



    //To know how to upload images and Test routes
    Route::post('/upload', [ProductController::class, 'upload']);
    Route::get('/products/{categoryId}', [ProductController::class, 'getAllProductsForCategory']);
});
