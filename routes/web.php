<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    Http::post("https://api.telegram.org/bot8532586515:AAFvyGK-E1apc9ETs_iMW-_HRIyTy1ke1h8/sendMessage", [
        'chat_id' => 955149250,
        'text' => 'Hello World!',
    ]);


});
