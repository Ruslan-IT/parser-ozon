<?php

use App\Http\Controllers\ProductAlertController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;


Route::get('/products/send-alerts', [ProductAlertController::class, 'sendAlerts']);




