<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => 'auth:sanctum'], function(){
    Route::apiResource('products', ProductController::class);
    Route::get('count-below-minimum-stock', [ProductController::class, 'countProductsBelowMinimumStock']);
    Route::get('count-expiring-soon', [ProductController::class, 'countProductsExpiringSoon']);
    Route::get('countUsers', [UserController::class, 'countUsers']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::get('/cart/items', [CartController::class, 'viewCart']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'removeItem']);
    Route::put('/cart/update/{id}', [CartController::class, 'updateItemQuantity']);
    Route::apiResource('orders', OrderController::class);
});


Route::post("login", [AuthController::class, 'login']);
Route::post("register", [AuthController::class, 'register']);
Route::post("logout", [AuthController::class, 'logout']);




