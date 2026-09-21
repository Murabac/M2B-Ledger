<?php

use Illuminate\Support\Facades\Route;

/*
| Wave 2 will register login, logout, agent sync, and read endpoints here.
*/

Route::get('/health', function () {
    return response()->json(['ok' => true]);
});
