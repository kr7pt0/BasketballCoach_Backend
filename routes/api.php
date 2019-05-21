<?php

use Illuminate\Http\Request;

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

// Auth::routes();

Route::post('/register', 'Auth\RegisterController@register');

Route::post('/login', 'Auth\LoginController@login');

Route::middleware('auth:api')->group(function () {
    
    Route::post('/logout', 'Auth\LoginController@logout');

    Route::namespace('Api')->group(function () {
        
        Route::get('/dashboard', 'HomeController@dashboard');
    });
});