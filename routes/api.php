<?php

use App\Http\Controllers\Api\ProductPriceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('throttle:3,1')->get('/product-price', [ProductPriceController::class, 'getPrice']);
