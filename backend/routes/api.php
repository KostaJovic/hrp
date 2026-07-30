<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => [
    'laravel' => app()->version(),
    'mysql' => DB::scalar('select version()'),
]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
