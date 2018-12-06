<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response()->json(\Carbon\Carbon::now()->toDateTimeString(), 200);
});

Route::get('/check-uw', function () {
    Artisan::call('check:upwork');

    return response()->json('success', 200);
});
