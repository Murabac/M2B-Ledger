<?php

use App\Http\Controllers\Dev\QuickLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/dev/quick-login', QuickLoginController::class)
    ->name('dev.quick-login');
