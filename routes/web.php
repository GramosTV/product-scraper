<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/products', function () {
        return view('products');
    })->name('products');
    Route::get('/products/{product}', function (\App\Models\Product $product) {
        return view('product', ['product' => $product]);
    })->name('product');;
    Route::get('/scraper', function () {
        return view('scraper');
    })->name('scraper');
    Route::get('/new-product', function () {
        return view('new-product');
    })->name('new-product');
});
