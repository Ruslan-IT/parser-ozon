<?php

use App\Http\Controllers\ProductAlertController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;


Route::get('/products/send-alerts', [ProductAlertController::class, 'sendAlerts']);





///usr/local/bin/php8.3 /home/info90zj/info90zj.beget.tech/artisan queue:work --queue=parsers --once
